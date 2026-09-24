<?php
namespace Controllers;

use Core\Controller;
use Core\DB;
use Core\CSRF;
use Core\Mailer;

class PasswordController extends Controller
{
    public function forgotForm()
    {
        // stejné jako login – bez sidebaru
        //$this->view('auth/forgot_password', ['title' => 'Zapomenuté heslo'], false);
        $view = 'auth/forgot_password';
        $title = 'Zapomenuté heslo';
        require __DIR__ . '/../Views/layout_guest.php';
    }

    public function sendReset()
    {
        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            return $this->redirect('/forgot-password');
        }

        $email = trim($_POST['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = "Zadej platný email.";
            return $this->redirect('/forgot-password');
        }

        $db = DB::get();

        // Bezpečnostní UX: vždy vrátíme stejnou hlášku, aby nešlo zjišťovat existenci účtu.
        $genericMsg = "Pokud účet existuje, poslali jsme odkaz pro obnovu hesla na zadaný email.";

        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['flash_success'] = $genericMsg;
            return $this->redirect('/forgot-password');
        }

        // token + expirace
        $token = bin2hex(random_bytes(24));
        $expires = (new \DateTime('+30 minutes'))->format('Y-m-d H:i:s');

        $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE id = ?");
        $stmt->execute([$token, $expires, (int)$user['id']]);

        $link = Mailer::appUrl('/reset-password/' . $token);

        $subject = "Obnova hesla";
        $html = "
          <div style='font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;line-height:1.5'>
            <h2>Obnova hesla</h2>
            <p>Pro nastavení nového hesla klikni na tlačítko:</p>
            <p><a href='{$link}' style='display:inline-block;padding:10px 14px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none'>Nastavit nové heslo</a></p>
            <p style='color:#6b7280'>Odkaz platí 30 minut. Pokud jsi o obnovu nežádal/a, email ignoruj.</p>
          </div>
        ";

        // Když mail selže, i tak necháme generic message, ale můžeš logovat.
        Mailer::send($email, $subject, $html);

        $_SESSION['flash_success'] = $genericMsg;
        return $this->redirect('/forgot-password');
    }

    public function resetForm($token)
    {
        $token = trim($token);

        $db = DB::get();
        $stmt = $db->prepare("
            SELECT id, reset_expires_at
            FROM users
            WHERE reset_token = ?
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        $valid = $user && !empty($user['reset_expires_at']) && strtotime($user['reset_expires_at']) >= time();

        $this->view('auth/reset_password', [
            'title' => 'Nastavit nové heslo',
            'token' => $token,
            'valid' => $valid
        ], false);
    }

    public function resetStore($token)
    {
        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            return $this->redirect('/reset-password/' . urlencode($token));
        }

        $p1 = $_POST['password'] ?? '';
        $p2 = $_POST['password_confirm'] ?? '';

        if (strlen($p1) < 8) {
            $_SESSION['flash_error'] = "Heslo musí mít alespoň 8 znaků.";
            return $this->redirect('/reset-password/' . urlencode($token));
        }
        if ($p1 !== $p2) {
            $_SESSION['flash_error'] = "Hesla se neshodují.";
            return $this->redirect('/reset-password/' . urlencode($token));
        }

        $db = DB::get();

        $stmt = $db->prepare("
            SELECT id, reset_expires_at
            FROM users
            WHERE reset_token = ?
            LIMIT 1
        ");
        $stmt->execute([trim($token)]);
        $user = $stmt->fetch();

        if (!$user || empty($user['reset_expires_at']) || strtotime($user['reset_expires_at']) < time()) {
            $_SESSION['flash_error'] = "Odkaz pro obnovu hesla je neplatný nebo vypršel.";
            return $this->redirect('/forgot-password');
        }

        $hash = password_hash($p1, PASSWORD_DEFAULT);

        // zneplatnit token po použití (one-time)
        $stmt = $db->prepare("
            UPDATE users
            SET password = ?, reset_token = NULL, reset_expires_at = NULL
            WHERE id = ?
        ");
        $stmt->execute([$hash, (int)$user['id']]);

        $_SESSION['flash_success'] = "Heslo bylo změněno. Nyní se přihlas.";
        return $this->redirect('/');
    }
}