<?php

namespace Controllers;

use Core\Controller;
use Core\CSRF;
use Core\DB;

class CompanyController extends Controller
{
    private const TERMS_VERSION = '1.0';
    private const GDPR_VERSION  = '1.0';
    private const DPA_VERSION   = '1.0';

    public function registerForm(): void
    {
        $this->view('company_register', [
            'title' => 'Registrace firmy'
        ], false);
    }

    public function registerCompany(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /register-company');
            exit;
        }

        CSRF::validate();

        $companyName = trim($_POST['company_name'] ?? '');
        $ico         = trim($_POST['ico'] ?? '');
        $dic         = trim($_POST['dic'] ?? '');
        $street      = trim($_POST['street'] ?? '');
        $city        = trim($_POST['city'] ?? '');
        $zip         = trim($_POST['zip'] ?? '');

        $firstName       = trim($_POST['first_name'] ?? '');
        $lastName        = trim($_POST['last_name'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $password        = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $acceptTerms = (int)($_POST['accept_terms'] ?? 0) === 1;
        $acceptGdpr  = (int)($_POST['accept_gdpr'] ?? 0) === 1;
        $acceptDpa   = (int)($_POST['accept_dpa'] ?? 0) === 1;

        if (
            $companyName === '' || $ico === '' || $street === '' || $city === '' || $zip === '' ||
            $firstName === '' || $lastName === '' || $email === '' || $password === '' || $passwordConfirm === ''
        ) {
            $_SESSION['flash_error'] = 'Vyplňte prosím všechna povinná pole.';
            header('Location: /register-company');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Email není platný.';
            header('Location: /register-company');
            exit;
        }

        if ($password !== $passwordConfirm) {
            $_SESSION['flash_error'] = 'Hesla se neshodují.';
            header('Location: /register-company');
            exit;
        }

        if (strlen($password) < 8) {
            $_SESSION['flash_error'] = 'Heslo musí mít alespoň 8 znaků.';
            header('Location: /register-company');
            exit;
        }

        if (!preg_match('/^\d{8}$/', $ico)) {
            $_SESSION['flash_error'] = 'IČO musí mít 8 číslic.';
            header('Location: /register-company');
            exit;
        }

        if (!$acceptTerms || !$acceptGdpr || !$acceptDpa) {
            $_SESSION['flash_error'] = 'Pro dokončení registrace musíte souhlasit se všemi právními dokumenty.';
            header('Location: /register-company');
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $db = DB::get();

        $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['flash_error'] = 'Uživatel s tímto emailem už existuje.';
            header('Location: /register-company');
            exit;
        }

        $stmt = $db->prepare('SELECT id FROM companies WHERE ico = ? LIMIT 1');
        $stmt->execute([$ico]);
        if ($stmt->fetch()) {
            $_SESSION['flash_error'] = 'Firma s tímto IČO již existuje.';
            header('Location: /register-company');
            exit;
        }

        $verifyToken   = bin2hex(random_bytes(24));
        $verifyExpires = (new \DateTime('+2 days'))->format('Y-m-d H:i:s');

        $acceptedAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $ipAddress  = $this->getClientIp();
        $userAgent  = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 1000);
        $source     = 'company_registration';

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("
                INSERT INTO companies (name, ico, dic, street, city, zip, billing_plan)
                VALUES (?, ?, ?, ?, ?, ?, 'free')
            ");
            $stmt->execute([
                $companyName,
                $ico,
                ($dic !== '' ? $dic : null),
                $street,
                $city,
                $zip
            ]);

            $companyId = (int)$db->lastInsertId();

            $stmt = $db->prepare("
                INSERT INTO users
                    (company_id, first_name, last_name, email, password, role, status, verify_token, verify_expires_at, email_verified_at)
                VALUES
                    (?, ?, ?, ?, ?, 'owner', 'invited', ?, ?, NULL)
            ");
            $stmt->execute([
                $companyId,
                $firstName,
                $lastName,
                $email,
                $hashedPassword,
                $verifyToken,
                $verifyExpires
            ]);

            $userId = (int)$db->lastInsertId();

            $this->storeLegalConsent($db, $companyId, $userId, 'terms', self::TERMS_VERSION, $acceptedAt, $ipAddress, $userAgent, $source);
            $this->storeLegalConsent($db, $companyId, $userId, 'gdpr',  self::GDPR_VERSION,  $acceptedAt, $ipAddress, $userAgent, $source);
            $this->storeLegalConsent($db, $companyId, $userId, 'dpa',   self::DPA_VERSION,   $acceptedAt, $ipAddress, $userAgent, $source);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $_SESSION['flash_error'] = 'Registraci se nepodařilo dokončit. Zkuste to prosím znovu.';
            header('Location: /register-company');
            exit;
        }

        $link = \Core\Mailer::appUrl('/verify/' . $verifyToken);

        $subject = 'Ověření emailu';
        $html = "
            <div style='font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;line-height:1.6;color:#111827'>
                <h2>Ověřte svůj email</h2>
                <p>Pro dokončení registrace potvrďte, že email patří vám:</p>
                <p>
                    <a href='{$link}' style='display:inline-block;padding:10px 14px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none'>
                        Ověřit email
                    </a>
                </p>
                <p style='color:#6b7280'>Odkaz platí 48 hodin.</p>

                <hr style='border:none;border-top:1px solid #e5e7eb;margin:24px 0'>

                <p style='font-size:14px;color:#6b7280'>
                    Při registraci jste potvrdili následující dokumenty:
                </p>
                <ul style='font-size:14px;color:#6b7280;padding-left:18px'>
                    <li><a href='" . \Core\Mailer::appUrl('/terms') . "'>Obchodní podmínky</a></li>
                    <li><a href='" . \Core\Mailer::appUrl('/gdpr') . "'>Zásady ochrany osobních údajů</a></li>
                    <li><a href='" . \Core\Mailer::appUrl('/dpa') . "'>Zpracovatelská smlouva (DPA)</a></li>
                </ul>
            </div>
        ";

        if (!\Core\Mailer::send($email, $subject, $html)) {
            $_SESSION['flash_error'] = 'Firma byla vytvořena, ale nepodařilo se odeslat ověřovací email. Kontaktujte podporu.';
            header('Location: /');
            exit;
        }

        $_SESSION['flash_success'] = 'Firma vytvořena. Zkontrolujte email a potvrďte ověření.';
        header('Location: /');
        exit;
    }

    private function storeLegalConsent(
        \PDO $db,
        int $companyId,
        int $userId,
        string $documentKey,
        string $documentVersion,
        string $acceptedAt,
        ?string $ipAddress,
        string $userAgent,
        string $source
    ): void {
        $stmt = $db->prepare("
            INSERT INTO legal_consents
                (company_id, user_id, document_key, document_version, accepted_at, ip_address, user_agent, source)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $companyId,
            $userId,
            $documentKey,
            $documentVersion,
            $acceptedAt,
            $ipAddress,
            $userAgent,
            $source
        ]);
    }

    private function getClientIp(): ?string
    {
        $keys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];

        foreach ($keys as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }

            $raw = trim((string)$_SERVER[$key]);
            if ($raw === '') {
                continue;
            }

            $ip = $raw;

            if ($key === 'HTTP_X_FORWARDED_FOR') {
                $parts = explode(',', $raw);
                $ip = trim($parts[0] ?? '');
            }

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return null;
    }
}
