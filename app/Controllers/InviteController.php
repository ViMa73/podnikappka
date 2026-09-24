<?php

namespace Controllers;

use Core\Controller;
use Core\CSRF;
use Core\DB;

class InviteController extends Controller
{
    public function show($token)
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT id, first_name, last_name, email, status, invite_expires_at
            FROM users
            WHERE invite_token = ?
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (
            !$user ||
            $user['status'] !== 'invited' ||
            !$user['invite_expires_at'] ||
            strtotime($user['invite_expires_at']) < time()
        ) {
            http_response_code(403);
            $view = 'invite/invalid';
            $title = 'Pozvánka';
            require __DIR__ . '/../Views/layout_guest.php';
            exit;
        }

        $view = 'invite/set_password';
        $title = 'Nastavit heslo';
        require __DIR__ . '/../Views/layout_guest.php';
    }

    public function store($token)
    {
        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            header("Location: /invite/$token");
            exit;
        }

        $p1 = $_POST['password'] ?? '';
        $p2 = $_POST['password_confirm'] ?? '';

        if (strlen($p1) < 8) {
            $_SESSION['flash_error'] = "Heslo musí mít alespoň 8 znaků.";
            header("Location: /invite/$token");
            exit;
        }

        if ($p1 !== $p2) {
            $_SESSION['flash_error'] = "Hesla se neshodují.";
            header("Location: /invite/$token");
            exit;
        }

        $db = DB::get();
        $stmt = $db->prepare("
            SELECT id, company_id, status, invite_expires_at
            FROM users
            WHERE invite_token = ?
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (
            !$user ||
            $user['status'] !== 'invited' ||
            !$user['invite_expires_at'] ||
            strtotime($user['invite_expires_at']) < time()
        ) {
            $_SESSION['flash_error'] = "Pozvánka je neplatná nebo vypršela.";
            header("Location: /");
            exit;
        }

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("
                UPDATE users
                SET password = ?, status = 'active', invite_token = NULL, invite_expires_at = NULL
                WHERE id = ?
            ");
            $stmt->execute([
                password_hash($p1, PASSWORD_DEFAULT),
                (int)$user['id']
            ]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $_SESSION['flash_error'] = "Aktivaci účtu se nepodařilo dokončit. Zkuste to prosím znovu.";
            header("Location: /invite/$token");
            exit;
        }

        $_SESSION['flash_success'] = "Heslo nastaveno. Nyní se přihlas.";
        header("Location: /");
        exit;
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
