<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Core\DB;
use Core\CSRF;

class ProfileController extends Controller
{
    public function index()
    {
        $db = DB::get();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([Auth::user()]);
        $user = $stmt->fetch();

        $view = 'profile';
        $title = 'Můj profil';
        require __DIR__ . '/../Views/layout.php';
    }

    public function updateProfile()
    {
        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            return $this->redirect('/profile');
        }

        $first = trim($_POST['first_name'] ?? '');
        $last  = trim($_POST['last_name'] ?? '');
        $theme = $_POST['theme'] ?? 'light';

        if ($first === '' || $last === '') {
            $_SESSION['flash_error'] = "Jméno i příjmení jsou povinné.";
            return $this->redirect('/profile');
        }

        if (!in_array($theme, ['light', 'dark', 'coffee', 'olive', 'slate', 'forest', 'midnight', 'crazy'], true)) {
            $theme = 'light';
        }

        $db = DB::get();
        $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, theme = ? WHERE id = ?");
        $stmt->execute([$first, $last, $theme, Auth::user()]);

        // sync do session
        $_SESSION['first_name'] = $first;
        $_SESSION['last_name'] = $last;
        $_SESSION['theme'] = $theme;

        $_SESSION['flash_success'] = "Profil uložen.";
        return $this->redirect('/profile');
    }

    public function uploadAvatar()
    {
        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            return $this->redirect('/profile');
        }

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = "Soubor se nepodařilo nahrát.";
            return $this->redirect('/profile');
        }

        // limity
        $maxBytes = 4 * 1024 * 1024; // 4 MB
        if ($_FILES['avatar']['size'] > $maxBytes) {
            $_SESSION['flash_error'] = "Soubor je příliš velký (max 4 MB).";
            return $this->redirect('/profile');
        }

        $tmp = $_FILES['avatar']['tmp_name'];
        $info = @getimagesize($tmp);
        if (!$info) {
            $_SESSION['flash_error'] = "Nahraný soubor není obrázek.";
            return $this->redirect('/profile');
        }

        $mime = $info['mime'] ?? '';
        $src = null;

        if ($mime === 'image/jpeg') $src = @imagecreatefromjpeg($tmp);
        elseif ($mime === 'image/png') $src = @imagecreatefrompng($tmp);
        elseif ($mime === 'image/webp') $src = @imagecreatefromwebp($tmp);
        else {
            $_SESSION['flash_error'] = "Nepodporovaný formát. Použij JPG/PNG/WEBP.";
            return $this->redirect('/profile');
        }

        if (!$src) {
            $_SESSION['flash_error'] = "Obrázek nelze načíst.";
            return $this->redirect('/profile');
        }

        $w = imagesx($src);
        $h = imagesy($src);

        // ořez na čtverec (střed)
        $side = min($w, $h);
        $sx = (int)(($w - $side) / 2);
        $sy = (int)(($h - $side) / 2);

        // cílová velikost
        $target = 256;
        $dst = imagecreatetruecolor($target, $target);

        // bílé pozadí (pro PNG s průhledností)
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $target, $target, $white);

        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $target, $target, $side, $side);

        // cesty
        $uploadDir = __DIR__ . '/../../uploads/avatars';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $filename = 'u' . Auth::user() . '_' . bin2hex(random_bytes(8)) . '.jpg';
        $absolute = $uploadDir . '/' . $filename;

        // uložit jako JPG (šetří místo)
        $saved = imagejpeg($dst, $absolute, 85);

        imagedestroy($src);
        imagedestroy($dst);

        if (!$saved) {
            $_SESSION['flash_error'] = "Nepodařilo se uložit avatar.";
            return $this->redirect('/profile');
        }

        // smazat původní avatar, pokud existuje a je v uploads/avatars
        $db = DB::get();
        $stmt = $db->prepare("SELECT avatar_path FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([Auth::user()]);
        $row = $stmt->fetch();
        $old = $row['avatar_path'] ?? null;

        if ($old && str_starts_with($old, '/uploads/avatars/')) {
            $oldAbs = __DIR__ . '/../../' . ltrim($old, '/');
            if (is_file($oldAbs)) @unlink($oldAbs);
        }

        $relative = '/uploads/avatars/' . $filename;

        $stmt = $db->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
        $stmt->execute([$relative, Auth::user()]);

        Auth::setAvatarPath($relative);

        $_SESSION['flash_success'] = "Profilová fotka byla změněna.";
        return $this->redirect('/profile');
    }

    public function changePassword()
    {
        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            return $this->redirect('/profile');
        }

        $current = $_POST['current_password'] ?? '';
        $new1 = $_POST['new_password'] ?? '';
        $new2 = $_POST['new_password_confirm'] ?? '';

        if ($new1 === '' || $new2 === '' || $current === '') {
            $_SESSION['flash_error'] = "Vyplň všechna pole pro změnu hesla.";
            return $this->redirect('/profile');
        }
        if ($new1 !== $new2) {
            $_SESSION['flash_error'] = "Nová hesla se neshodují.";
            return $this->redirect('/profile');
        }
        if (strlen($new1) < 8) {
            $_SESSION['flash_error'] = "Nové heslo musí mít alespoň 8 znaků.";
            return $this->redirect('/profile');
        }

        $db = DB::get();
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([Auth::user()]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password'])) {
            $_SESSION['flash_error'] = "Původní heslo není správné.";
            return $this->redirect('/profile');
        }

        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([password_hash($new1, PASSWORD_DEFAULT), Auth::user()]);

        // po změně hesla odhlásit
        $_SESSION['flash_success'] = "Heslo změněno. Prosím přihlas se znovu.";
        // robustní logout (stejně jako jsme řešili)
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        @session_destroy();

        // po destroy znovu start, aby šel flash
        @session_start();
        $_SESSION['flash_success'] = "Heslo změněno. Prosím přihlas se znovu.";

        return $this->redirect('/');
    }
}
