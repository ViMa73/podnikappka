<?php
namespace Controllers;

use Core\Controller;
use Core\DB;
use Core\Auth;
use Core\Mailer;
use Core\CSRF;

class UserController extends Controller
{
    public function invite()
    {
        $token = bin2hex(random_bytes(32));

        $stmt = DB::get()->prepare("
            INSERT INTO invitations (company_id, email, token, expires_at)
            VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 2 DAY))
        ");

        $stmt->execute([
            Auth::companyId(),
            $_POST['email'],
            $token
        ]);

        $link = BASE_URL . "invite/$token";

        Mailer::send($_POST['email'], "Pozvánka do aplikace",
            "Klikni zde pro registraci: <a href='$link'>$link</a>"
        );

        header("Location: /dashboard");
    }

    public function acceptInvitationForm($token)
    {
        $view = 'accept_invite';
        $title = 'Dokončení registrace';
        require __DIR__ . '/../Views/layout.php';
    }

    public function acceptInvitation($token)
    {
        if (!CSRF::check($_POST['_csrf'])) exit("CSRF chyba");

        $stmt = DB::get()->prepare("
            SELECT * FROM invitations
            WHERE token = ? AND accepted = 0 AND expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $invite = $stmt->fetch();

        if (!$invite) exit("Neplatná pozvánka");

        DB::get()->prepare("
            INSERT INTO users (company_id, email, password, role)
            VALUES (?, ?, ?, ?)
        ")->execute([
            $invite['company_id'],
            $invite['email'],
            password_hash($_POST['password'], PASSWORD_DEFAULT),
            $_POST['role']
        ]);

        DB::get()->prepare("
            UPDATE invitations SET accepted = 1 WHERE id = ?
        ")->execute([$invite['id']]);

        header("Location: /");
    }
}