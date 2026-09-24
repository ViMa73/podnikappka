<?php
namespace Core;

use Core\DB;

class Auth
{
    public static function login($email, $password): bool
    {
        $stmt = DB::get()->prepare("
            SELECT u.*,
                c.name AS company_name,
                c.ico AS company_ico,
                c.dic AS company_dic,
                c.street AS company_street,
                c.city AS company_city,
                c.zip AS company_zip,
                c.allow_manager_settings AS allow_manager_settings,
                c.status AS company_status
            FROM users u
            LEFT JOIN companies c ON c.id = u.company_id
            WHERE u.email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['flash_error'] = 'Takový email neznáme.';
            return false;
        }

        if (($user['status'] ?? 'active') !== 'active') {
            $_SESSION['flash_error'] = 'Tento uživatel není aktivní.';
            return false;
        }

        // superadmin bez firmy pustíme dál
        $isSuperAdmin = (int)($user['is_super_admin'] ?? 0) === 1;

        if (!$isSuperAdmin && $user['company_id'] !== null) {
            if (($user['company_status'] ?? 'active') !== 'active') {
                $_SESSION['flash_error'] = 'Vaše firma byla zablokována!';
                return false;
            }
        }

        if (!password_verify($password, $user['password'])) {
            $_SESSION['flash_error'] = 'Neplatné heslo.';
            return false;
        }

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['company_id'] = $user['company_id'] !== null ? (int)$user['company_id'] : null;
        $_SESSION['company_name'] = $user['company_name'] ?? '';
        $_SESSION['company_allow_manager_settings'] = (int)($user['allow_manager_settings'] ?? 0);

        $_SESSION['email'] = $user['email'] ?? '';
        $_SESSION['first_name'] = $user['first_name'] ?? '';
        $_SESSION['last_name'] = $user['last_name'] ?? '';
        $_SESSION['avatar_path'] = $user['avatar_path'] ?? null;
        $_SESSION['theme'] = $user['theme'] ?? 'light';
        $_SESSION['role'] = $user['role'] ?? null;
        $_SESSION['is_super_admin'] = (int)($user['is_super_admin'] ?? 0) === 1;

        return true;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_destroy();
        }
    }

    public static function user(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public static function companyId(): ?int
    {
        return array_key_exists('company_id', $_SESSION) && $_SESSION['company_id'] !== null
            ? (int)$_SESSION['company_id']
            : null;
    }

    public static function role(): ?string
    {
        return isset($_SESSION['role']) && $_SESSION['role'] !== null
            ? (string)$_SESSION['role']
            : null;
    }

    public static function isSuperAdmin(): bool
    {
        return !empty($_SESSION['is_super_admin']);
    }

    public static function isCompanyUser(): bool
    {
        return self::check() && self::companyId() !== null;
    }

    public static function isOwner(): bool
    {
        return self::role() === 'owner';
    }

    public static function isManager(): bool
    {
        return self::role() === 'manager';
    }

    public static function isWorker(): bool
    {
        return self::role() === 'worker';
    }

    public static function theme(): string
    {
        return $_SESSION['theme'] ?? 'light';
    }

    public static function email(): string
    {
        return $_SESSION['email'] ?? '';
    }

    public static function firstName(): string
    {
        return $_SESSION['first_name'] ?? '';
    }

    public static function lastName(): string
    {
        return $_SESSION['last_name'] ?? '';
    }

    public static function fullName(): string
    {
        $fn = trim(self::firstName());
        $ln = trim(self::lastName());
        $name = trim($fn . ' ' . $ln);

        return $name !== '' ? $name : (self::email() ?: 'Uživatel');
    }

    public static function companyName(): string
    {
        return $_SESSION['company_name'] ?? '';
    }

    public static function avatarPath(): ?string
    {
        return $_SESSION['avatar_path'] ?? null;
    }

    public static function setAvatarPath(?string $path): void
    {
        $_SESSION['avatar_path'] = $path;
    }

    public static function avatarUrl(int $size = 96): string
    {
        $local = self::avatarPath();
        if ($local) {
            return $local . '?v=' . time();
        }

        return "/uploads/avatars/user.png";
    }

    public static function allowManagerSettings(): bool
    {
        if (!self::isCompanyUser()) {
            return false;
        }

        return (int)($_SESSION['company_allow_manager_settings'] ?? 0) === 1;
    }

    public static function canAccessSettings(): bool
    {
        if (!self::isCompanyUser()) {
            return false;
        }

        if (self::isOwner()) {
            return true;
        }

        if (self::isManager()) {
            return self::allowManagerSettings();
        }

        return false;
    }

    public static function canEditSettings(): bool
    {
        return self::canAccessSettings();
    }

    public static function setAllowManagerSettings(bool $enabled): void
    {
        $_SESSION['company_allow_manager_settings'] = $enabled ? 1 : 0;
    }

    public static function canManageUsers(): bool
    {
        if (!self::isCompanyUser()) {
            return false;
        }

        return in_array(self::role(), ['owner', 'manager'], true);
    }

    public static function canViewExports(): bool
    {
        if (!self::check() || !self::isCompanyUser()) {
            return false;
        }

        if (self::isOwner()) {
            return true;
        }

        if (!self::isManager()) {
            return false;
        }

        $companyId = self::companyId();
        if ($companyId === null) {
            return false;
        }

        $db = DB::get();
        $stmt = $db->prepare("
            SELECT manager_can_view_exports
            FROM companies
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$companyId]);
        $company = $stmt->fetch();

        return !empty($company['manager_can_view_exports']);
    }

    public static function canAccessWasteReports(): bool
    {
        if (!self::check() || !self::isCompanyUser()) {
            return false;
        }

        if (!\Core\Feature::enabled('waste_reports')) {
            return false;
        }

        if (self::isOwner()) {
            return true;
        }

        if (self::isManager()) {
            $companyId = self::companyId();
            if ($companyId === null) {
                return false;
            }

            $db = DB::get();
            $stmt = $db->prepare("
                SELECT manager_can_view_waste_reports
                FROM companies
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([$companyId]);
            $company = $stmt->fetch();

            return !empty($company['manager_can_view_waste_reports']);
        }

        return false;
    }
}
