<?php
namespace Controllers;

use Core\Controller;
use Core\DB;

class VerifyController extends Controller
{
    public function verify($token)
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT id, status, verify_expires_at
            FROM users
            WHERE verify_token = ?
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (
            !$user ||
            ($user['status'] ?? '') !== 'invited' ||
            empty($user['verify_expires_at']) ||
            strtotime($user['verify_expires_at']) < time()
        ) {
            $_SESSION['flash_error'] = "Ověřovací odkaz je neplatný nebo vypršel.";
            header("Location: /");
            exit;
        }

        $stmt = $db->prepare("
            UPDATE users
            SET status='active',
                email_verified_at = NOW(),
                verify_token = NULL,
                verify_expires_at = NULL
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);

        $_SESSION['flash_success'] = "Email ověřen. Nyní se přihlas.";
        header("Location: /");
        exit;
    }
}
