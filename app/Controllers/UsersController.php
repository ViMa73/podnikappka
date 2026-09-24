<?php
namespace Controllers;

use Core\Auth;
use Core\Controller;
use Core\CSRF;
use Core\DB;
use Core\Mailer;

class UsersController extends Controller
{
    private function forbid()
    {
        http_response_code(403);
        $view = 'errors/403';
        $title = 'Nemáš oprávnění';
        require __DIR__ . '/../Views/layout.php';
        exit;
    }

    public function index()
    {
        if (!Auth::canManageUsers()) $this->forbid();

        $db = DB::get();
        $stmt = $db->prepare("SELECT id, first_name, last_name, email, role, status FROM users WHERE company_id = ? ORDER BY id ASC");
        $stmt->execute([Auth::companyId()]);
        $users = $stmt->fetchAll();

        $view = 'users/index';
        $title = 'Uživatelé';
        require __DIR__ . '/../Views/layout.php';
    }

    public function invite()
    {
        if (!Auth::canManageUsers()) $this->forbid();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            header("Location: /users");
            exit;
        }

        $first = trim($_POST['first_name'] ?? '');
        $last  = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = $_POST['role'] ?? 'worker';

        if ($first === '' || $last === '' || $email === '') {
            $_SESSION['flash_error'] = "Vyplň všechna povinná pole.";
            header("Location: /users");
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = "Email není platný.";
            header("Location: /users");
            exit;
        }

        if (!in_array($role, ['owner','manager','worker'], true)) {
            $role = 'worker';
        }

        // bezpečnost: nedovol managerovi zvát ownera (pokud chceš)
        if (Auth::role() !== 'owner' && $role === 'owner') {
            $_SESSION['flash_error'] = "Pouze owner může vytvořit owner roli.";
            header("Location: /users");
            exit;
        }

        $db = DB::get();

        // email unikátně globálně? pokud u tebe není, tak alespoň per-company
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND company_id = ? LIMIT 1");
        $stmt->execute([$email, Auth::companyId()]);
        if ($stmt->fetch()) {
            $_SESSION['flash_error'] = "Uživatel s tímto emailem už ve firmě existuje.";
            header("Location: /users");
            exit;
        }

        $token = bin2hex(random_bytes(24)); // 48 chars
        $expires = (new \DateTime('+7 days'))->format('Y-m-d H:i:s');

        $stmt = $db->prepare("
            INSERT INTO users (company_id, first_name, last_name, email, role, status, invite_token, invite_expires_at, password)
            VALUES (?, ?, ?, ?, ?, 'invited', ?, ?, NULL)
        ");
        $stmt->execute([Auth::companyId(), $first, $last, $email, $role, $token, $expires]);

        // email
        $link = Mailer::appUrl('/invite/' . $token);

        $subject = "Pozvánka do aplikace";
        $html = "
            <div style='font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;line-height:1.6;color:#111827'>
              <h2>Byl/a jste pozván/a do aplikace</h2>
              <p>Dokončete registraci nastavením hesla:</p>
              <p>
                <a href='{$link}' style='display:inline-block;padding:10px 14px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none'>
                  Nastavit heslo
                </a>
              </p>
              <p style='color:#6b7280'>Odkaz platí 7 dní.</p>
            </div>
        ";

        if (!Mailer::send($email, $subject, $html)) {
            $_SESSION['flash_error'] = "Uživatel byl přidán, ale email se nepodařilo odeslat (mail()).";
            header("Location: /users");
            exit;
        }

        $_SESSION['flash_success'] = "Pozvánka odeslána.";
        header("Location: /users");
        exit;
    }

    public function delete($id)
    {
        if (!\Core\Auth::canManageUsers()) {
            $this->forbid();
        }

        if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář.";
            header("Location: /users/" . (int)$id);
            exit;
        }

        $db = \Core\DB::get();

        // cílový uživatel
        $stmt = $db->prepare("
            SELECT id, role
            FROM users
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");

        $stmt->execute([(int)$id, \Core\Auth::companyId()]);
        $target = $stmt->fetch();

        if (!$target) {
            http_response_code(404);
            exit;
        }

        if ($target['id'] == $_SESSION['user_id']) {
            $_SESSION['flash_error'] = "Nemůžeš smazat svůj vlastní účet.";
            header("Location: /users");
            exit;
        }

        $currentRole = \Core\Auth::role();

        // manager může mazat pouze worker
        if ($currentRole === 'manager' && $target['role'] !== 'worker') {
            $this->forbid();
        }

        // ochrana proti smazání posledního ownera
        if ($target['role'] === 'owner') {

            $stmt = $db->prepare("
                SELECT COUNT(*)
                FROM users
                WHERE company_id = ? AND role = 'owner'
            ");
            $stmt->execute([\Core\Auth::companyId()]);
            $owners = (int)$stmt->fetchColumn();

            if ($owners <= 1) {
                $_SESSION['flash_error'] = "Nelze smazat posledního vlastníka firmy.";
                header("Location: /users/" . (int)$id);
                exit;
            }
        }

        // smazání
        $stmt = $db->prepare("
            DELETE FROM users
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([(int)$id, \Core\Auth::companyId()]);

        $_SESSION['flash_success'] = "Uživatel byl smazán.";
        header("Location: /users");
        exit;
    }

    private function canViewUser(array $target): bool
    {
        // worker nesmí vůbec
        if (!\Core\Auth::canManageUsers()) return false; // u tebe to vrací owner/manager
        // company guard se řeší SQL dotazem (id+company_id)
        return true;
    }

    private function canEditBasic(array $target): bool
    {
        $role = \Core\Auth::role();
        if ($role === 'owner') return true;

        // manager může editovat pouze worker
        if ($role === 'manager' && ($target['role'] ?? '') === 'worker') return true;

        return false;
    }

    private function canEditExtra(array $target): bool
    {
        // extra karta: owner u všech, manager jen u worker
        return $this->canEditBasic($target);
    }

    private function canViewExtra(array $target): bool
    {
        $role = \Core\Auth::role();
        if ($role === 'owner') return true;

        // manager může vidět managera a workera
        if ($role === 'manager' && ($target['role'] ?? '') === 'worker') return true;
        if ($role === 'manager' && ($target['role'] ?? '') === 'manager') return true;

        return false;
    }

    /**
     * GET /users/{id}
     */
     public function show($id)
    {
        if (!\Core\Auth::canManageUsers()) $this->forbid();

        $db = \Core\DB::get();
        $stmt = $db->prepare("
            SELECT *
            FROM users
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([(int)$id, \Core\Auth::companyId()]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            $view = 'errors/404';
            $title = 'Nenalezeno';
            require __DIR__ . '/../Views/layout.php';
            exit;
        }

        if (!$this->canViewUser($user)) $this->forbid();

        $canEditBasic = $this->canEditBasic($user);
        $canEditExtra = $this->canEditExtra($user);
        $canViewExtra = $this->canViewExtra($user);

        $currentYear = (int)date('Y');

        $stmt = $db->prepare("
            SELECT COALESCE(SUM(total_hours), 0) AS used_hours
            FROM vacations
            WHERE company_id = ?
              AND user_id = ?
              AND YEAR(date_from) = ?
        ");
        $stmt->execute([
            \Core\Auth::companyId(),
            (int)$user['id'],
            $currentYear
        ]);

        $vacationUsedYear = (float)($stmt->fetchColumn() ?? 0);

        $vacationEntitlementYear = (float)($user['vacation_hours_year'] ?? 0);
        $vacationRemainingYear = $vacationEntitlementYear - $vacationUsedYear;
        if ($vacationRemainingYear < 0) {
            $vacationRemainingYear = 0;
        }

        $economicIndicatorTopLevelGroups = [];
        $economicIndicatorTopLevelIndicators = [];

        if (\Core\Feature::enabled('economic_indicators')) {
            $stmt = $db->prepare("
                SELECT id, name
                FROM economic_indicator_definitions
                WHERE company_id = ?
                  AND parent_id IS NULL
                  AND type = 'group'
                ORDER BY sort_order ASC, id ASC
            ");
            $stmt->execute([\Core\Auth::companyId()]);
            $economicIndicatorTopLevelGroups = $stmt->fetchAll() ?: [];

            $stmt = $db->prepare("
                SELECT id, name
                FROM economic_indicator_definitions
                WHERE company_id = ?
                  AND parent_id IS NULL
                  AND type = 'indicator'
                ORDER BY sort_order ASC, id ASC
            ");
            $stmt->execute([\Core\Auth::companyId()]);
            $economicIndicatorTopLevelIndicators = $stmt->fetchAll() ?: [];
        }

        $view = 'users/show';
        $title = 'Detail uživatele';
        require __DIR__ . '/../Views/layout.php';
    }

    /**
     * POST /users/{id}/update
     */
     public function update($id)
     {
         if (!\Core\Auth::canManageUsers()) $this->forbid();
         if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
             $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
             header("Location: /users/" . (int)$id);
             exit;
         }

         $db = \Core\DB::get();
         $stmt = $db->prepare("
             SELECT id, role
             FROM users
             WHERE id = ? AND company_id = ?
             LIMIT 1
         ");
         $stmt->execute([(int)$id, \Core\Auth::companyId()]);
         $target = $stmt->fetch();
         if (!$target) {
             http_response_code(404);
             $view = 'errors/404';
             $title = 'Nenalezeno';
             require __DIR__ . '/../Views/layout.php';
             exit;
         }

         if (!$this->canEditBasic($target)) $this->forbid();

         $first = trim($_POST['first_name'] ?? '');
         $last  = trim($_POST['last_name'] ?? '');
         $email = trim($_POST['email'] ?? '');
         $birthDate = trim($_POST['birth_date'] ?? '');
         $phone = trim($_POST['phone'] ?? '');

         if ($first === '' || $last === '' || $email === '') {
             $_SESSION['flash_error'] = "Vyplň jméno, příjmení a email.";
             header("Location: /users/" . (int)$id);
             exit;
         }

         if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
             $_SESSION['flash_error'] = "Email není platný.";
             header("Location: /users/" . (int)$id);
             exit;
         }

         if ($birthDate !== '') {
             $dt = \DateTime::createFromFormat('Y-m-d', $birthDate);
             $dateIsValid = $dt && $dt->format('Y-m-d') === $birthDate;

             if (!$dateIsValid) {
                 $_SESSION['flash_error'] = "Datum narození není platné.";
                 header("Location: /users/" . (int)$id);
                 exit;
             }
         } else {
             $birthDate = null;
         }

         $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
         $stmt->execute([$email, (int)$id]);
         if ($stmt->fetch()) {
             $_SESSION['flash_error'] = "Tento email už někdo používá.";
             header("Location: /users/" . (int)$id);
             exit;
         }

         $newRole = $target['role'];

         if (\Core\Auth::role() === 'owner') {
             $postedRole = $_POST['role'] ?? $target['role'];
             if (!in_array($postedRole, ['owner','manager','worker'], true)) {
                 $postedRole = $target['role'];
             }

             if ($target['role'] === 'owner' && $postedRole !== 'owner') {
                 $stmt = $db->prepare("
                     SELECT COUNT(*)
                     FROM users
                     WHERE company_id = ? AND role = 'owner'
                 ");
                 $stmt->execute([\Core\Auth::companyId()]);
                 $owners = (int)$stmt->fetchColumn();

                 if ($owners <= 1) {
                     $_SESSION['flash_error'] = "Nelze snížit oprávnění posledního vlastníka firmy.";
                     header("Location: /users/" . (int)$id);
                     exit;
                 }
             }

             $newRole = $postedRole;
         }

         $stmt = $db->prepare("
             UPDATE users
             SET first_name = ?, last_name = ?, email = ?, role = ?, birth_date = ?, phone = ?
             WHERE id = ? AND company_id = ?
         ");
         $stmt->execute([
             $first,
             $last,
             $email,
             $newRole,
             $birthDate,
             $phone !== '' ? $phone : null,
             (int)$id,
             \Core\Auth::companyId()
         ]);

         $_SESSION['flash_success'] = "Uživatel uložen.";
         header("Location: /users/" . (int)$id);
         exit;
     }

    /**
     * POST /users/{id}/extra
     */
     public function updateExtra($id)
    {
        if (!\Core\Auth::canManageUsers()) $this->forbid();

        if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            header("Location: /users/" . (int)$id);
            exit;
        }

        $db = \Core\DB::get();
        $stmt = $db->prepare("
            SELECT id, role
            FROM users
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([(int)$id, \Core\Auth::companyId()]);
        $target = $stmt->fetch();

        if (!$target) {
            http_response_code(404);
            $view = 'errors/404';
            $title = 'Nenalezeno';
            require __DIR__ . '/../Views/layout.php';
            exit;
        }

        if (!$this->canEditExtra($target)) $this->forbid();

        $payrollActive = isset($_POST['payroll_active']) ? 1 : 0;

        $payrollSalaryMode = trim((string)($_POST['payroll_salary_mode'] ?? 'company_default'));
        if (!in_array($payrollSalaryMode, ['company_default', 'fixed', 'hourly', 'mixed'], true)) {
            $payrollSalaryMode = 'company_default';
        }

        $payrollFixedSalary = trim((string)($_POST['payroll_fixed_salary'] ?? ''));
        $payrollHourlyRate = trim((string)($_POST['payroll_hourly_rate'] ?? ''));

        $payrollFixedSalary = ($payrollFixedSalary === '') ? null : str_replace(',', '.', $payrollFixedSalary);
        $payrollHourlyRate = ($payrollHourlyRate === '') ? null : str_replace(',', '.', $payrollHourlyRate);

        if ($payrollFixedSalary !== null && !is_numeric($payrollFixedSalary)) {
            $_SESSION['flash_error'] = "Pevná mzda musí být číslo.";
            header("Location: /users/" . (int)$id);
            exit;
        }

        if ($payrollHourlyRate !== null && !is_numeric($payrollHourlyRate)) {
            $_SESSION['flash_error'] = "Hodinová sazba musí být číslo.";
            header("Location: /users/" . (int)$id);
            exit;
        }

        $payrollCompanyBonusMode = trim((string)($_POST['payroll_company_bonus_mode'] ?? 'company_default'));
        if (!in_array($payrollCompanyBonusMode, ['company_default', 'none', 'custom'], true)) {
            $payrollCompanyBonusMode = 'company_default';
        }

        $payrollCustomBonusSourceType = trim((string)($_POST['payroll_custom_bonus_source_type'] ?? ''));
        if (!in_array($payrollCustomBonusSourceType, ['indicator', 'group'], true)) {
            $payrollCustomBonusSourceType = null;
        }

        $payrollCustomBonusSourceId = (int)($_POST['payroll_custom_bonus_source_id'] ?? 0);
        if ($payrollCustomBonusSourceId <= 0) {
            $payrollCustomBonusSourceId = null;
        }

        $payrollCustomBonusCalcType = trim((string)($_POST['payroll_custom_bonus_calc_type'] ?? 'percent'));
        if (!in_array($payrollCustomBonusCalcType, ['percent', 'fixed'], true)) {
            $payrollCustomBonusCalcType = 'percent';
        }

        $payrollCustomBonusValue = trim((string)($_POST['payroll_custom_bonus_value'] ?? ''));
        $payrollCustomBonusValue = ($payrollCustomBonusValue === '') ? null : str_replace(',', '.', $payrollCustomBonusValue);

        if ($payrollCustomBonusValue !== null && !is_numeric($payrollCustomBonusValue)) {
            $_SESSION['flash_error'] = "Hodnota vlastní prémie musí být číslo.";
            header("Location: /users/" . (int)$id);
            exit;
        }

        if ($payrollCompanyBonusMode !== 'custom') {
            $payrollCustomBonusSourceType = null;
            $payrollCustomBonusSourceId = null;
            $payrollCustomBonusCalcType = null;
            $payrollCustomBonusValue = null;
        }

        $payrollWeekendBonusMode = trim((string)($_POST['payroll_weekend_bonus_mode'] ?? 'company_default'));
        if (!in_array($payrollWeekendBonusMode, ['company_default', 'none', 'custom'], true)) {
            $payrollWeekendBonusMode = 'company_default';
        }

        $payrollWeekendBonusType = trim((string)($_POST['payroll_weekend_bonus_type'] ?? 'shift_amount'));
        if (!in_array($payrollWeekendBonusType, ['shift_amount', 'hour_amount', 'hourly_rate_percent'], true)) {
            $payrollWeekendBonusType = 'shift_amount';
        }

        $payrollWeekendBonusValue = trim((string)($_POST['payroll_weekend_bonus_value'] ?? ''));
        $payrollWeekendBonusValue = ($payrollWeekendBonusValue === '') ? null : str_replace(',', '.', $payrollWeekendBonusValue);

        if ($payrollWeekendBonusValue !== null && !is_numeric($payrollWeekendBonusValue)) {
            $_SESSION['flash_error'] = "Víkendový příplatek musí být číslo.";
            header("Location: /users/" . (int)$id);
            exit;
        }

        if ($payrollWeekendBonusMode !== 'custom') {
            $payrollWeekendBonusType = null;
            $payrollWeekendBonusValue = null;
        }

        $payrollHolidayBonusMode = trim((string)($_POST['payroll_holiday_bonus_mode'] ?? 'company_default'));
        if (!in_array($payrollHolidayBonusMode, ['company_default', 'none', 'custom'], true)) {
            $payrollHolidayBonusMode = 'company_default';
        }

        $payrollHolidayBonusType = trim((string)($_POST['payroll_holiday_bonus_type'] ?? 'shift_amount'));
        if (!in_array($payrollHolidayBonusType, ['shift_amount', 'hour_amount', 'hourly_rate_percent'], true)) {
            $payrollHolidayBonusType = 'shift_amount';
        }

        $payrollHolidayBonusValue = trim((string)($_POST['payroll_holiday_bonus_value'] ?? ''));
        $payrollHolidayBonusValue = ($payrollHolidayBonusValue === '') ? null : str_replace(',', '.', $payrollHolidayBonusValue);

        if ($payrollHolidayBonusValue !== null && !is_numeric($payrollHolidayBonusValue)) {
            $_SESSION['flash_error'] = "Sváteční příplatek musí být číslo.";
            header("Location: /users/" . (int)$id);
            exit;
        }

        if ($payrollHolidayBonusMode !== 'custom') {
            $payrollHolidayBonusType = null;
            $payrollHolidayBonusValue = null;
        }

        $workload = trim($_POST['workload_hours'] ?? '');
        $vacationHoursYear = trim($_POST['vacation_hours_year'] ?? '');

        $workload = ($workload === '') ? null : str_replace(',', '.', $workload);
        $vacationHoursYear = ($vacationHoursYear === '') ? null : str_replace(',', '.', $vacationHoursYear);

        if ($workload !== null && !is_numeric($workload)) {
            $_SESSION['flash_error'] = "Úvazek musí být číslo (např. 7,5).";
            header("Location: /users/" . (int)$id);
            exit;
        }

        if ($vacationHoursYear !== null && !is_numeric($vacationHoursYear)) {
            $_SESSION['flash_error'] = "Dovolená musí být číslo (např. 160).";
            header("Location: /users/" . (int)$id);
            exit;
        }

        $stmt = $db->prepare("
            UPDATE users
            SET payroll_active = ?,
                payroll_salary_mode = ?,
                payroll_fixed_salary = ?,
                payroll_hourly_rate = ?,

                payroll_company_bonus_mode = ?,
                payroll_custom_bonus_source_type = ?,
                payroll_custom_bonus_source_id = ?,
                payroll_custom_bonus_calc_type = ?,
                payroll_custom_bonus_value = ?,

                payroll_weekend_bonus_mode = ?,
                payroll_weekend_bonus_type = ?,
                payroll_weekend_bonus_value = ?,

                payroll_holiday_bonus_mode = ?,
                payroll_holiday_bonus_type = ?,
                payroll_holiday_bonus_value = ?,

                workload_hours = ?,
                vacation_hours_year = ?
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([
            $payrollActive,
            $payrollSalaryMode,
            $payrollFixedSalary,
            $payrollHourlyRate,

            $payrollCompanyBonusMode,
            $payrollCustomBonusSourceType,
            $payrollCustomBonusSourceId,
            $payrollCustomBonusCalcType,
            $payrollCustomBonusValue,

            $payrollWeekendBonusMode,
            $payrollWeekendBonusType,
            $payrollWeekendBonusValue,

            $payrollHolidayBonusMode,
            $payrollHolidayBonusType,
            $payrollHolidayBonusValue,

            $workload,
            $vacationHoursYear,
            (int)$id,
            \Core\Auth::companyId()
        ]);

        $_SESSION['flash_success'] = "Rozšiřující údaje uloženy.";
        header("Location: /users/" . (int)$id);
        exit;
    }

}
