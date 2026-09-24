<?php
/** @var string $step */
$root = dirname(__DIR__);

function installer_h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function installer_redirect(string $url): void { header('Location: ' . $url); exit; }
function installer_csrf(): string {
    if (empty($_SESSION['installer_csrf'])) $_SESSION['installer_csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['installer_csrf'];
}
function installer_check_csrf(): void {
    if (!isset($_POST['_csrf'], $_SESSION['installer_csrf']) || !hash_equals($_SESSION['installer_csrf'], (string)$_POST['_csrf'])) {
        throw new RuntimeException('Platnost formuláře vypršela. Zkus to prosím znovu.');
    }
}
function installer_pdo(array $db): PDO {
    $host = trim((string)($db['host'] ?? 'localhost'));
    $port = max(1, (int)($db['port'] ?? 3306));
    $name = trim((string)($db['name'] ?? ''));
    $user = trim((string)($db['user'] ?? ''));
    if ($name === '' || $user === '') throw new RuntimeException('Vyplň název databáze a databázového uživatele.');
    return new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, (string)($db['pass'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
function installer_split_sql(string $sql): array {
    $statements=[]; $buf=''; $quote=null; $len=strlen($sql); $lineComment=false; $blockComment=false;
    for ($i=0;$i<$len;$i++) {
        $c=$sql[$i]; $n=$i+1<$len?$sql[$i+1]:'';
        if ($lineComment) { if ($c==="\n") { $lineComment=false; $buf.=$c; } continue; }
        if ($blockComment) { if ($c==='*' && $n==='/') { $blockComment=false; $i++; } continue; }
        if ($quote===null) {
            if (($c==='-' && $n==='-' && ($i+2>=$len || ctype_space($sql[$i+2]))) || $c==='#') { $lineComment=true; if($c==='-')$i++; continue; }
            if ($c==='/' && $n==='*') { $blockComment=true; $i++; continue; }
            if ($c==="'" || $c==='"' || $c==='`') { $quote=$c; $buf.=$c; continue; }
            if ($c===';') { $s=trim($buf); if($s!=='')$statements[]=$s; $buf=''; continue; }
        } else {
            if ($c==='\\' && $quote!=='`' && $i+1<$len) { $buf.=$c.$n; $i++; continue; }
            if ($c===$quote) $quote=null;
        }
        $buf.=$c;
    }
    $s=trim($buf); if($s!=='')$statements[]=$s;
    return $statements;
}
function installer_write_local(string $root, array $db, array $smtp): void {
    $base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if ($base === '') $base = '/'; else $base .= '/';
    $v = fn($x) => var_export((string)$x, true);
    $content = "<?php\n// Vygenerováno instalačním průvodcem PodnikAppka.\n"
        . "define('DB_HOST', ".$v($db['host']).");\n"
        . "define('DB_PORT', ".(int)$db['port'].");\n"
        . "define('DB_NAME', ".$v($db['name']).");\n"
        . "define('DB_USER', ".$v($db['user']).");\n"
        . "define('DB_PASS', ".$v($db['pass']).");\n"
        . "define('SMTP_HOST', ".$v($smtp['host'] ?? '').");\n"
        . "define('SMTP_USER', ".$v($smtp['user'] ?? '').");\n"
        . "define('SMTP_PASS', ".$v($smtp['pass'] ?? '').");\n"
        . "define('SMTP_PORT', ".(int)($smtp['port'] ?? 587).");\n"
        . "define('SMTP_ENCRYPTION', ".$v($smtp['encryption'] ?? 'tls').");\n"
        . "define('BASE_URL', ".$v($base).");\n";
    if (@file_put_contents($root . '/config/local.php', $content, LOCK_EX) === false) throw new RuntimeException('Nepodařilo se zapsat config/local.php. Ověř práva zápisu složky config.');
}

$requirements = [
    'PHP 8.0+' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'PDO' => extension_loaded('pdo'),
    'PDO MySQL' => extension_loaded('pdo_mysql'),
    'mbstring' => extension_loaded('mbstring'),
    'config zapisovatelný' => is_writable($root . '/config'),
    'storage zapisovatelný' => is_writable($root . '/storage'),
];
$requirementsOk = !in_array(false, $requirements, true);
$step = (string)($_GET['step'] ?? '1');
$error = null;

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        installer_check_csrf();
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'database') {
            if (!$requirementsOk) throw new RuntimeException('Server nesplňuje všechny požadavky instalace.');
            $db = [
                'host'=>trim((string)($_POST['db_host'] ?? 'localhost')), 'port'=>(int)($_POST['db_port'] ?? 3306),
                'name'=>trim((string)($_POST['db_name'] ?? '')), 'user'=>trim((string)($_POST['db_user'] ?? '')), 'pass'=>(string)($_POST['db_pass'] ?? ''),
            ];
            $pdo=installer_pdo($db); $pdo->query('SELECT 1');
            $_SESSION['installer_db']=$db;
            $_SESSION['installer_smtp']=[
                'host'=>trim((string)($_POST['smtp_host'] ?? '')), 'user'=>trim((string)($_POST['smtp_user'] ?? '')), 'pass'=>(string)($_POST['smtp_pass'] ?? ''),
                'port'=>(int)($_POST['smtp_port'] ?? 587), 'encryption'=>trim((string)($_POST['smtp_encryption'] ?? 'tls')),
            ];
            installer_redirect('/?step=3');
        }
        if ($action === 'install') {
            $db=$_SESSION['installer_db'] ?? null; if(!$db) throw new RuntimeException('Chybí ověřené připojení k databázi. Vrať se na předchozí krok.');
            $company=[
                'name'=>trim((string)($_POST['company_name'] ?? '')), 'ico'=>trim((string)($_POST['company_ico'] ?? '')), 'dic'=>trim((string)($_POST['company_dic'] ?? '')),
                'street'=>trim((string)($_POST['company_street'] ?? '')), 'city'=>trim((string)($_POST['company_city'] ?? '')), 'zip'=>trim((string)($_POST['company_zip'] ?? '')),
            ];
            $admin=[
                'first'=>trim((string)($_POST['admin_first'] ?? '')), 'last'=>trim((string)($_POST['admin_last'] ?? '')), 'email'=>strtolower(trim((string)($_POST['admin_email'] ?? ''))),
                'password'=>(string)($_POST['admin_password'] ?? ''), 'password2'=>(string)($_POST['admin_password2'] ?? ''),
            ];
            if($company['name']==='' || $company['street']==='' || $company['city']==='' || $company['zip']==='') throw new RuntimeException('Vyplň název a adresu firmy.');
            if($admin['first']==='' || $admin['last']==='' || !filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Vyplň platné údaje administrátora.');
            if(strlen($admin['password'])<8) throw new RuntimeException('Heslo musí mít alespoň 8 znaků.');
            if($admin['password']!==$admin['password2']) throw new RuntimeException('Hesla se neshodují.');

            $pdo=installer_pdo($db);
            // Odmítneme nechtěnou instalaci do již používané PodnikAppka databáze.
            $existing=$pdo->query("SHOW TABLES LIKE 'companies'")->fetchColumn();
            if($existing) {
                $count=(int)$pdo->query('SELECT COUNT(*) FROM companies')->fetchColumn();
                if($count>0) throw new RuntimeException('Databáze už obsahuje firmu PodnikAppka. Pro novou self-hosted instalaci použij prázdnou databázi.');
            }

            $sql=file_get_contents($root.'/database/install.sql');
            if($sql===false) throw new RuntimeException('Chybí database/install.sql.');
            foreach(installer_split_sql($sql) as $statement) $pdo->exec($statement);

            // Spustíme všechny migrace a zapíšeme jejich stav, aby systémová stránka začínala čistě.
            $files=glob($root.'/database/migrations/*.php') ?: []; sort($files, SORT_STRING);
            foreach($files as $file) {
                $name=basename($file,'.php');
                $callable=require $file;
                if(!is_callable($callable)) throw new RuntimeException("Migrace {$name} není spustitelná.");
                $callable($pdo);
                $st=$pdo->prepare("INSERT INTO migrations(name,executed_at,executed_by_user_id,status,message) VALUES(?,NOW(),NULL,'success','Instalace') ON DUPLICATE KEY UPDATE status='success', message='Instalace'");
                $st->execute([$name]);
            }

            $pdo->beginTransaction();
            try {
                $st=$pdo->prepare("INSERT INTO companies(name,ico,dic,street,city,zip,status) VALUES(?,?,?,?,?,?,'active')");
                $st->execute([$company['name'],$company['ico'],$company['dic']!==''?$company['dic']:null,$company['street'],$company['city'],$company['zip']]);
                $companyId=(int)$pdo->lastInsertId();
                $st=$pdo->prepare("INSERT INTO users(company_id,first_name,last_name,email,password,role,theme,status,is_super_admin,email_verified_at) VALUES(?,?,?,?,?,'owner','light','active',0,NOW())");
                $st->execute([$companyId,$admin['first'],$admin['last'],$admin['email'],password_hash($admin['password'],PASSWORD_DEFAULT)]);
                $st=$pdo->prepare("INSERT INTO company_features(company_id,feature_id,enabled) SELECT ?,id,1 FROM features ON DUPLICATE KEY UPDATE enabled=1");
                $st->execute([$companyId]);
                $pdo->commit();
            } catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack(); throw $e; }

            installer_write_local($root,$db,$_SESSION['installer_smtp'] ?? []);
            $lock="installed_at=".date(DATE_ATOM)."\nedition=self_hosted\n";
            if(@file_put_contents($root.'/storage/installed.lock',$lock,LOCK_EX)===false) throw new RuntimeException('Databáze je vytvořena, ale nepodařilo se vytvořit storage/installed.lock.');
            unset($_SESSION['installer_db'],$_SESSION['installer_smtp'],$_SESSION['installer_csrf']);
            $_SESSION['installer_done']=true;
            installer_redirect('/?installed=1');
        }
    }
} catch(Throwable $e) { $error=$e->getMessage(); }

$db=$_SESSION['installer_db'] ?? ['host'=>'localhost','port'=>3306,'name'=>'','user'=>'','pass'=>''];
$smtp=$_SESSION['installer_smtp'] ?? ['host'=>'','port'=>587,'user'=>'','pass'=>'','encryption'=>'tls'];
?><!doctype html>
<html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalace PodnikAppka</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-100 text-slate-900 min-h-screen"><main class="max-w-3xl mx-auto px-4 py-10">
<div class="mb-8"><img src="/uploads/podnikappka_logotyp.png" alt="PodnikAppka" class="h-12 max-w-full object-contain object-left"><h1 class="text-3xl font-bold mt-5">Instalace self-hosted verze</h1><p class="text-slate-600 mt-2">Průvodce připraví databázi a první účet vlastníka firmy.</p></div>
<div class="flex gap-2 mb-6 text-sm"><?php foreach([1=>'Server',2=>'Databáze',3=>'Firma a admin'] as $n=>$label): ?><div class="flex-1 rounded-xl px-3 py-2 <?=((int)$step===$n)?'bg-blue-600 text-white':'bg-white border'?>"><b><?=$n?>.</b> <?=installer_h($label)?></div><?php endforeach; ?></div>
<?php if($error): ?><div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800"><?=installer_h($error)?></div><?php endif; ?>
<div class="bg-white rounded-2xl shadow-sm border p-6">
<?php if($step==='1'): ?><h2 class="text-xl font-semibold mb-5">Kontrola serveru</h2><div class="space-y-3"><?php foreach($requirements as $name=>$ok): ?><div class="flex justify-between border-b pb-2"><span><?=installer_h($name)?></span><b class="<?=$ok?'text-green-600':'text-red-600'?>"><?=$ok?'V pořádku':'Chybí / nelze'?></b></div><?php endforeach; ?></div><div class="mt-6"><a href="/?step=2" class="inline-block px-5 py-3 rounded-xl bg-blue-600 text-white font-semibold <?=$requirementsOk?'':'pointer-events-none opacity-50'?>">Pokračovat</a></div>
<?php elseif($step==='2'): ?><h2 class="text-xl font-semibold mb-5">Databáze a e-mail</h2><form method="post" class="space-y-5"><input type="hidden" name="_csrf" value="<?=installer_h(installer_csrf())?>"><input type="hidden" name="action" value="database"><div class="grid sm:grid-cols-2 gap-4"><label>DB host<input class="mt-1 w-full border rounded-lg p-2" name="db_host" value="<?=installer_h((string)$db['host'])?>" required></label><label>Port<input class="mt-1 w-full border rounded-lg p-2" type="number" name="db_port" value="<?=installer_h((string)$db['port'])?>" required></label><label>Název databáze<input class="mt-1 w-full border rounded-lg p-2" name="db_name" value="<?=installer_h((string)$db['name'])?>" required></label><label>DB uživatel<input class="mt-1 w-full border rounded-lg p-2" name="db_user" value="<?=installer_h((string)$db['user'])?>" required></label><label class="sm:col-span-2">DB heslo<input class="mt-1 w-full border rounded-lg p-2" type="password" name="db_pass" value="<?=installer_h((string)$db['pass'])?>"></label></div><hr><div><h3 class="font-semibold">SMTP (volitelné, doporučeno pro pozvánky a obnovu hesla)</h3><div class="grid sm:grid-cols-2 gap-4 mt-3"><label>SMTP host<input class="mt-1 w-full border rounded-lg p-2" name="smtp_host" value="<?=installer_h((string)$smtp['host'])?>"></label><label>Port<input class="mt-1 w-full border rounded-lg p-2" type="number" name="smtp_port" value="<?=installer_h((string)$smtp['port'])?>"></label><label>Uživatel / e-mail<input class="mt-1 w-full border rounded-lg p-2" name="smtp_user" value="<?=installer_h((string)$smtp['user'])?>"></label><label>Heslo<input class="mt-1 w-full border rounded-lg p-2" type="password" name="smtp_pass" value="<?=installer_h((string)$smtp['pass'])?>"></label><label>Šifrování<select class="mt-1 w-full border rounded-lg p-2" name="smtp_encryption"><option value="tls">TLS</option><option value="ssl">SSL</option><option value="">Bez šifrování</option></select></label></div></div><button class="px-5 py-3 rounded-xl bg-blue-600 text-white font-semibold">Otestovat DB a pokračovat</button></form>
<?php else: ?><h2 class="text-xl font-semibold mb-5">Firma a první administrátor</h2><form method="post" class="space-y-6"><input type="hidden" name="_csrf" value="<?=installer_h(installer_csrf())?>"><input type="hidden" name="action" value="install"><div><h3 class="font-semibold mb-3">Firma</h3><div class="grid sm:grid-cols-2 gap-4"><label class="sm:col-span-2">Název firmy<input class="mt-1 w-full border rounded-lg p-2" name="company_name" required></label><label>IČO<input class="mt-1 w-full border rounded-lg p-2" name="company_ico"></label><label>DIČ<input class="mt-1 w-full border rounded-lg p-2" name="company_dic"></label><label class="sm:col-span-2">Ulice<input class="mt-1 w-full border rounded-lg p-2" name="company_street" required></label><label>Město<input class="mt-1 w-full border rounded-lg p-2" name="company_city" required></label><label>PSČ<input class="mt-1 w-full border rounded-lg p-2" name="company_zip" required></label></div></div><hr><div><h3 class="font-semibold mb-3">Vlastník / administrátor firmy</h3><div class="grid sm:grid-cols-2 gap-4"><label>Jméno<input class="mt-1 w-full border rounded-lg p-2" name="admin_first" required></label><label>Příjmení<input class="mt-1 w-full border rounded-lg p-2" name="admin_last" required></label><label class="sm:col-span-2">E-mail<input class="mt-1 w-full border rounded-lg p-2" type="email" name="admin_email" required></label><label>Heslo<input class="mt-1 w-full border rounded-lg p-2" type="password" name="admin_password" minlength="8" required></label><label>Heslo znovu<input class="mt-1 w-full border rounded-lg p-2" type="password" name="admin_password2" minlength="8" required></label></div></div><div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm">Instalace je určena pro prázdnou databázi. PodnikAppka je svobodný software pod GNU AGPLv3. Provozovatel instance odpovídá za její konfiguraci, zabezpečení, zálohování a zpracování dat.</div><button class="px-5 py-3 rounded-xl bg-green-600 text-white font-semibold">Nainstalovat PodnikAppku</button></form><?php endif; ?>
</div></main></body></html>
