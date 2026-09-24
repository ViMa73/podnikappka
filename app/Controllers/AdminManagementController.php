<?php
namespace Controllers;

use Core\Auth;
use Core\Controller;
use Core\CSRF;
use Core\DB;

class AdminManagementController extends Controller
{
    private function requireAdmin(): void
    {
        if (!Auth::check()) {
            header('Location: /');
            exit;
        }

        if (!Auth::isSuperAdmin()) {
            http_response_code(403);
            echo 'Přístup odepřen.';
            exit;
        }
    }

    public function admins(): void
    {
        $this->requireAdmin();

        $db = DB::get();

        $stmt = $db->query("
            SELECT
                u.id,
                u.email,
                u.first_name,
                u.last_name,
                u.role,
                u.company_id,
                c.name AS company_name
            FROM users u
            LEFT JOIN companies c ON c.id = u.company_id
            WHERE u.is_super_admin = 1
            ORDER BY u.first_name ASC, u.last_name ASC, u.email ASC
        ");
        $admins = $stmt->fetchAll() ?: [];

        $stmt = $db->query("
            SELECT
                u.id,
                u.email,
                u.first_name,
                u.last_name,
                u.role,
                u.company_id,
                c.name AS company_name
            FROM users u
            LEFT JOIN companies c ON c.id = u.company_id
            WHERE u.is_super_admin = 0
            ORDER BY
                c.name ASC,
                u.first_name ASC,
                u.last_name ASC,
                u.email ASC
        ");
        $availableUsers = $stmt->fetchAll() ?: [];

        $view = 'admin/admins';
        $title = 'Správa adminů';
        require __DIR__ . '/../Views/layout.php';
    }

    public function promoteExistingUser(): void
    {
        $this->requireAdmin();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            header('Location: /admin/admins');
            exit;
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            $_SESSION['flash_error'] = 'Nebyl vybrán uživatel.';
            header('Location: /admin/admins');
            exit;
        }

        $stmt = DB::get()->prepare("
            UPDATE users
            SET is_super_admin = 1
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);

        $_SESSION['flash_success'] = 'Uživatel byl povýšen na admina.';
        header('Location: /admin/admins');
        exit;
    }

    public function createAdmin(): void
    {
        $this->requireAdmin();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            header('Location: /admin/admins');
            exit;
        }

        $email = trim((string)($_POST['email'] ?? ''));
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName = trim((string)($_POST['last_name'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $_SESSION['flash_error'] = 'Email a heslo jsou povinné.';
            header('Location: /admin/admins');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Email nemá platný formát.';
            header('Location: /admin/admins');
            exit;
        }

        if (mb_strlen($password) < 6) {
            $_SESSION['flash_error'] = 'Heslo musí mít alespoň 6 znaků.';
            header('Location: /admin/admins');
            exit;
        }

        $db = DB::get();

        $stmt = $db->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);

        if ($stmt->fetchColumn()) {
            $_SESSION['flash_error'] = 'Uživatel s tímto emailem už existuje.';
            header('Location: /admin/admins');
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            INSERT INTO users (
                company_id,
                email,
                password,
                first_name,
                last_name,
                role,
                status,
                theme,
                is_super_admin
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            null,
            $email,
            $hash,
            $firstName !== '' ? $firstName : null,
            $lastName !== '' ? $lastName : null,
            null,
            'active',
            'light',
            1,
        ]);

        $_SESSION['flash_success'] = 'Nový admin byl vytvořen.';
        header('Location: /admin/admins');
        exit;
    }

    public function removeAdmin(): void
    {
        $this->requireAdmin();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            header('Location: /admin/admins');
            exit;
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            $_SESSION['flash_error'] = 'Neplatný admin.';
            header('Location: /admin/admins');
            exit;
        }

        if ((int)Auth::user() === $userId) {
            $_SESSION['flash_error'] = 'Nemůžeš odebrat admin práva sám sobě.';
            header('Location: /admin/admins');
            exit;
        }

        $stmt = DB::get()->prepare("
            UPDATE users
            SET is_super_admin = 0
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);

        $_SESSION['flash_success'] = 'Admin práva byla odebrána.';
        header('Location: /admin/admins');
        exit;
    }

    public function companies(): void
    {
        $this->requireAdmin();

        $db = DB::get();

        $stmt = $db->query("
            SELECT
                c.id,
                c.name,
                c.ico,
                c.city,
                c.created_at,
                c.status AS company_status,
                c.blocked_at,
                c.blocked_reason,
                s.plan AS subscription_plan,
                s.ended_at AS subscription_ended_at,
                (
                    SELECT COUNT(*)
                    FROM users u2
                    WHERE u2.company_id = c.id
                ) AS users_count
            FROM companies c
            LEFT JOIN subscriptions s
              ON s.id = (
                  SELECT s2.id
                  FROM subscriptions s2
                  WHERE s2.company_id = c.id
                    AND s2.status = 'active'
                  ORDER BY s2.id DESC
                  LIMIT 1
              )
            ORDER BY c.name ASC, c.id ASC
        ");
        $companies = $stmt->fetchAll() ?: [];

        $stmt = $db->query("
            SELECT
                u.id,
                u.company_id,
                u.first_name,
                u.last_name,
                u.email,
                u.role,
                u.status
            FROM users u
            WHERE u.company_id IS NOT NULL
            ORDER BY u.company_id ASC, u.first_name ASC, u.last_name ASC, u.email ASC
        ");
        $allUsers = $stmt->fetchAll() ?: [];

        $usersByCompany = [];
        foreach ($allUsers as $user) {
            $companyId = (int)$user['company_id'];
            if (!isset($usersByCompany[$companyId])) {
                $usersByCompany[$companyId] = [];
            }
            $usersByCompany[$companyId][] = $user;
        }

        $view = 'admin/companies';
        $title = 'Firmy';
        require __DIR__ . '/../Views/layout.php';
    }

    public function saveCompanyLicense(): void
    {
        $this->requireAdmin();

        if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            header('Location: /admin/companies');
            exit;
        }

        $companyId = (int)($_POST['company_id'] ?? 0);
        $plan = trim((string)($_POST['plan'] ?? 'paid'));
        $expiresAtRaw = trim((string)($_POST['ended_at'] ?? ''));

        if ($companyId <= 0) {
            $_SESSION['flash_error'] = 'Neplatná firma.';
            header('Location: /admin/companies');
            exit;
        }

        if (!in_array($plan, ['free', 'paid'], true)) {
            $_SESSION['flash_error'] = 'Neplatný plán.';
            header('Location: /admin/companies');
            exit;
        }

        $db = \Core\DB::get();

        $stmt = $db->prepare("
            SELECT *
            FROM subscriptions
            WHERE company_id = ?
              AND status = 'active'
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$companyId]);
        $currentSubscription = $stmt->fetch() ?: null;

        $db->beginTransaction();

        try {
            $now = new \DateTimeImmutable('now');
            $startedAt = $now->format('Y-m-d H:i:s');
            $endedAt = null;

            if ($plan === 'paid') {
                if ($expiresAtRaw === '') {
                    throw new \RuntimeException('Pro paid licenci musí být vyplněné datum expirace.');
                }

                $expires = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $expiresAtRaw)
                    ?: \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $expiresAtRaw)
                    ?: \DateTimeImmutable::createFromFormat('Y-m-d', $expiresAtRaw);

                if (!$expires) {
                    throw new \RuntimeException('Datum expirace nemá platný formát.');
                }

                $endedAt = $expires->format('Y-m-d H:i:s');
            }

            if ($currentSubscription) {
                $stmt = $db->prepare("
                    UPDATE subscriptions
                    SET status = 'ended',
                        ended_at = COALESCE(ended_at, NOW())
                    WHERE id = ?
                ");
                $stmt->execute([(int)$currentSubscription['id']]);
            }

            $stmt = $db->prepare("
                INSERT INTO subscriptions (
                    company_id,
                    plan,
                    status,
                    started_at,
                    ended_at
                ) VALUES (?, ?, 'active', ?, ?)
            ");
            $stmt->execute([
                $companyId,
                $plan,
                $startedAt,
                $endedAt
            ]);

            $db->commit();

            $_SESSION['flash_success'] = $plan === 'paid'
                ? 'Paid licence byla uložena.'
                : 'Firma byla přepnuta na free licenci.';
        } catch (\Throwable $e) {
            $db->rollBack();
            $_SESSION['flash_error'] = 'Nepodařilo se uložit licenci: ' . $e->getMessage();
        }

        header('Location: /admin/companies');
        exit;
    }

    public function blockCompany(): void
    {
        $this->requireAdmin();

        if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            header('Location: /admin/companies');
            exit;
        }

        $companyId = (int)($_POST['company_id'] ?? 0);
        $reason = trim((string)($_POST['blocked_reason'] ?? ''));

        if ($companyId <= 0) {
            $_SESSION['flash_error'] = 'Neplatná firma.';
            header('Location: /admin/companies');
            exit;
        }

        $stmt = \Core\DB::get()->prepare("
            UPDATE companies
            SET status = 'blocked',
                blocked_at = NOW(),
                blocked_reason = ?
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([
            $reason !== '' ? $reason : null,
            $companyId
        ]);

        $_SESSION['flash_success'] = 'Firma byla zablokována.';
        header('Location: /admin/companies');
        exit;
    }

    public function unblockCompany(): void
    {
        $this->requireAdmin();

        if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            header('Location: /admin/companies');
            exit;
        }

        $companyId = (int)($_POST['company_id'] ?? 0);

        if ($companyId <= 0) {
            $_SESSION['flash_error'] = 'Neplatná firma.';
            header('Location: /admin/companies');
            exit;
        }

        $stmt = \Core\DB::get()->prepare("
            UPDATE companies
            SET status = 'active',
                blocked_at = NULL,
                blocked_reason = NULL
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$companyId]);

        $_SESSION['flash_success'] = 'Firma byla odblokována.';
        header('Location: /admin/companies');
        exit;
    }
}
