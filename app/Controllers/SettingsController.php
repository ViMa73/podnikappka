<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Core\DB;
use Core\CSRF;

class SettingsController extends Controller
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
        if (!Auth::canAccessSettings()) {
            $this->forbid();
        }

        $db = DB::get();
        $stmt = $db->prepare("
            SELECT *
            FROM companies
            WHERE id = ? LIMIT 1
        ");
        $stmt->execute([Auth::companyId()]);
        $company = $stmt->fetch();

        $wasteReportPlaces = [];

        if (\Core\Feature::enabled('waste_reports')) {
            $stmt = \Core\DB::get()->prepare("
                SELECT *
                FROM waste_report_places
                WHERE company_id = ?
                ORDER BY id ASC
            ");
            $stmt->execute([\Core\Auth::companyId()]);
            $wasteReportPlaces = $stmt->fetchAll();
        }

        $temperaturePlaces = [];
        $sterilizationPlaces = [];

        if (\Core\Feature::enabled('temperatures')) {
            $stmt = \Core\DB::get()->prepare("
                SELECT *
                FROM temperature_places
                WHERE company_id = ?
                ORDER BY id ASC
            ");
            $stmt->execute([\Core\Auth::companyId()]);
            $temperaturePlaces = $stmt->fetchAll();
        }

        if (\Core\Feature::enabled('sterilization_drying')) {
            $stmt = \Core\DB::get()->prepare("
                SELECT *
                FROM sterilization_places
                WHERE company_id = ?
                ORDER BY id ASC
            ");
            $stmt->execute([\Core\Auth::companyId()]);
            $sterilizationPlaces = $stmt->fetchAll();
        }

        $economicIndicatorDefinitions = $this->economicIndicatorDefinitions();
        $economicIndicatorGroupOptions = $this->economicIndicatorGroupOptions();

        $economicIndicatorTopLevelGroups = [];
        $economicIndicatorTopLevelIndicators = [];

        if (\Core\Feature::enabled('economic_indicators')) {
            $db = DB::get();

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

        $view = 'settings';
        $title = 'Nastavení';
        require __DIR__ . '/../Views/layout.php';
    }

    public function updateExportsCompany(): void
    {
        if (!Auth::canAccessSettings()) {
            $this->forbid();
        }

        if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            $this->redirect('/settings');
        }

        if (!\Core\Auth::canEditSettings()) {
            $_SESSION['flash_error'] = "Nemáš oprávnění upravovat nastavení.";
            $this->redirect('/settings');
        }

        $managerCanViewExports = !empty($_POST['manager_can_view_exports']) ? 1 : 0;
        $mealVoucherExportEnabled = !empty($_POST['meal_voucher_export_enabled']) ? 1 : 6;

        $mealVoucherMinHoursRaw = str_replace(',', '.', trim((string)($_POST['meal_voucher_min_hours'] ?? '0')));

        if ($mealVoucherMinHoursRaw === '') {
            $mealVoucherMinHoursRaw = '0';
        }

        if (!is_numeric($mealVoucherMinHoursRaw)) {
            $_SESSION['flash_error'] = "Počet hodin pro nárok na stravenku musí být číslo.";
            $this->redirect('/settings');
        }

        $mealVoucherMinHours = (float)$mealVoucherMinHoursRaw;

        if ($mealVoucherMinHours < 0) {
            $_SESSION['flash_error'] = "Počet hodin pro nárok na stravenku nesmí být záporný.";
            $this->redirect('/settings');
        }

        $db = \Core\DB::get();
        $stmt = $db->prepare("
            UPDATE companies
            SET manager_can_view_exports = ?,
                meal_voucher_export_enabled = ?,
                meal_voucher_min_hours = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $managerCanViewExports,
            $mealVoucherExportEnabled,
            $mealVoucherMinHours,
            \Core\Auth::companyId()
        ]);

        $_SESSION['flash_success'] = "Nastavení exportů bylo uloženo.";
        $this->redirect('/settings');
    }

    public function createTemperaturePlace()
    {
        if (!\Core\Auth::canEditSettings()) $this->forbid();

        if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            header("Location: /settings");
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $humidityEnabled = isset($_POST['humidity_enabled']) ? 1 : 0;

        if ($name === '') {
            $_SESSION['flash_error'] = "Vyplň název teploměru.";
            header("Location: /settings");
            exit;
        }

        $db = \Core\DB::get();
        $stmt = $db->prepare("
            INSERT INTO temperature_places (company_id, name, humidity_enabled)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            \Core\Auth::companyId(),
            $name,
            $humidityEnabled
        ]);

        $_SESSION['flash_success'] = "Teploměr byl vytvořen.";
        header("Location: /settings");
        exit;
    }

    public function updateTemperaturePlace($id)
    {
        if (!\Core\Auth::canEditSettings()) $this->forbid();

        if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'message' => 'Neplatný formulář (CSRF).']);
            exit;
        }

        $db = \Core\DB::get();

        $stmt = $db->prepare("
            SELECT id
            FROM temperature_places
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([(int)$id, \Core\Auth::companyId()]);
        $place = $stmt->fetch();

        if (!$place) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'Teploměr nebyl nalezen.']);
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $humidityEnabled = isset($_POST['humidity_enabled']) ? 1 : 0;

        if ($name === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Vyplň název teploměru.']);
            exit;
        }

        $stmt = $db->prepare("
            UPDATE temperature_places
            SET name = ?, humidity_enabled = ?
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([
            $name,
            $humidityEnabled,
            (int)$id,
            \Core\Auth::companyId()
        ]);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
        exit;
    }

    public function deleteTemperaturePlace($id)
    {
        if (!\Core\Auth::canEditSettings()) $this->forbid();

        if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            header("Location: /settings");
            exit;
        }

        $db = \Core\DB::get();
        $stmt = $db->prepare("
            DELETE FROM temperature_places
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([
            (int)$id,
            \Core\Auth::companyId()
        ]);

        $_SESSION['flash_success'] = "Teploměr byl smazán.";
        header("Location: /settings");
        exit;
    }

    public function createSterilizationPlace()
    {
        if (!\Core\Auth::canEditSettings()) $this->forbid();

        if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            header("Location: /settings");
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $isSterilizer = isset($_POST['is_sterilizer']) ? 1 : 0;
        $isDrying = isset($_POST['is_drying']) ? 1 : 0;

        if ($name === '') {
            $_SESSION['flash_error'] = "Vyplň název místa.";
            header("Location: /settings");
            exit;
        }

        $db = \Core\DB::get();
        $stmt = $db->prepare("
            INSERT INTO sterilization_places (company_id, name, is_sterilizer, is_drying)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            \Core\Auth::companyId(),
            $name,
            $isSterilizer,
            $isDrying
        ]);

        $_SESSION['flash_success'] = "Místo bylo vytvořeno.";
        header("Location: /settings");
        exit;
    }

    public function updateSterilizationPlace($id)
    {
        if (!\Core\Auth::canEditSettings()) $this->forbid();

        if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'message' => 'Neplatný formulář (CSRF).']);
            exit;
        }

        $db = \Core\DB::get();

        $stmt = $db->prepare("
            SELECT id
            FROM sterilization_places
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([(int)$id, \Core\Auth::companyId()]);
        $place = $stmt->fetch();

        if (!$place) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'Místo nebylo nalezeno.']);
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $isSterilizer = isset($_POST['is_sterilizer']) ? 1 : 0;
        $isDrying = isset($_POST['is_drying']) ? 1 : 0;

        if ($name === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Vyplň název místa.']);
            exit;
        }

        $stmt = $db->prepare("
            UPDATE sterilization_places
            SET name = ?, is_sterilizer = ?, is_drying = ?
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([
            $name,
            $isSterilizer,
            $isDrying,
            (int)$id,
            \Core\Auth::companyId()
        ]);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
        exit;
    }

    public function deleteSterilizationPlace($id)
    {
        if (!\Core\Auth::canEditSettings()) $this->forbid();

        if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            header("Location: /settings");
            exit;
        }

        $db = \Core\DB::get();
        $stmt = $db->prepare("
            DELETE FROM sterilization_places
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([
            (int)$id,
            \Core\Auth::companyId()
        ]);

        $_SESSION['flash_success'] = "Místo bylo smazáno.";
        header("Location: /settings");
        exit;
    }

    public function updatePayrolls(): void
    {
        if (!Auth::canEditSettings()) {
            $this->forbid();
        }

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            header("Location: /settings");
            exit;
        }

        if (!\Core\Feature::enabled('payrolls')) {
            $_SESSION['flash_error'] = "Modul Výplaty není zapnutý.";
            header("Location: /settings");
            exit;
        }

        $payrollLockAttendance = isset($_POST['payroll_lock_attendance_after_creation']) ? 1 : 0;
        $payrollAllowManager = isset($_POST['payroll_allow_manager']) ? 1 : 0;

        $salaryType = (string)($_POST['payroll_default_salary_type'] ?? 'fixed');
        if (!in_array($salaryType, ['fixed', 'hourly', 'mixed'], true)) {
            $salaryType = 'fixed';
        }

        $componentFixedSalary = isset($_POST['payroll_component_fixed_salary']) ? 1 : 0;
        $componentHourlyWage = isset($_POST['payroll_component_hourly_wage']) ? 1 : 0;
        $componentCompanyBonus = isset($_POST['payroll_component_company_bonus']) ? 1 : 0;
        $componentWeekendBonus = isset($_POST['payroll_component_weekend_bonus']) ? 1 : 0;
        $componentHolidayBonus = isset($_POST['payroll_component_holiday_bonus']) ? 1 : 0;

        $weekendBonusType = (string)($_POST['payroll_weekend_bonus_type'] ?? 'shift_amount');
        if (!in_array($weekendBonusType, ['shift_amount', 'hour_amount', 'hourly_rate_percent'], true)) {
            $weekendBonusType = 'shift_amount';
        }

        $holidayBonusType = (string)($_POST['payroll_holiday_bonus_type'] ?? 'shift_amount');
        if (!in_array($holidayBonusType, ['shift_amount', 'hour_amount', 'hourly_rate_percent'], true)) {
            $holidayBonusType = 'shift_amount';
        }

        $weekendBonusValue = (float)str_replace(',', '.', (string)($_POST['payroll_weekend_bonus_value'] ?? '0'));
        $holidayBonusValue = (float)str_replace(',', '.', (string)($_POST['payroll_holiday_bonus_value'] ?? '0'));

        if ($weekendBonusValue < 0) {
            $weekendBonusValue = 0;
        }

        if ($holidayBonusValue < 0) {
            $holidayBonusValue = 0;
        }

        $payrollOverlapRule = (string)($_POST['payroll_overlap_rule'] ?? 'sum');
        if (!in_array($payrollOverlapRule, ['sum', 'higher', 'lower', 'holiday', 'weekend'], true)) {
            $payrollOverlapRule = 'sum';
        }

        $companyBonusEnabled = isset($_POST['payroll_default_company_bonus_enabled']) ? 1 : 0;

        $companyBonusSourceType = (string)($_POST['payroll_default_company_bonus_source_type'] ?? '');
        if (!in_array($companyBonusSourceType, ['indicator', 'group'], true)) {
            $companyBonusSourceType = null;
        }

        $companyBonusSourceId = (int)($_POST['payroll_default_company_bonus_source_id'] ?? 0);
        if ($companyBonusSourceId <= 0) {
            $companyBonusSourceId = null;
        }

        $companyBonusCalcType = (string)($_POST['payroll_default_company_bonus_calc_type'] ?? 'percent');
        if (!in_array($companyBonusCalcType, ['percent', 'fixed'], true)) {
            $companyBonusCalcType = 'percent';
        }

        $companyBonusValue = (float)str_replace(',', '.', (string)($_POST['payroll_default_company_bonus_value'] ?? '0'));
        if ($companyBonusValue < 0) {
            $companyBonusValue = 0;
        }

        if (!$companyBonusEnabled) {
            $companyBonusSourceType = null;
            $companyBonusSourceId = null;
            $companyBonusValue = 0;
        }

        $roundingType = (string)($_POST['payroll_rounding_type'] ?? 'none');
        if (!in_array($roundingType, ['none', '1', '10', '100'], true)) {
            $roundingType = 'none';
        }

        $countOvertime = isset($_POST['payroll_count_overtime']) ? 1 : 0;

        $payrollFixedSalaryShortfallMode = (string)($_POST['payroll_fixed_salary_shortfall_mode'] ?? 'full');
        if (!in_array($payrollFixedSalaryShortfallMode, ['full', 'proportional', 'zero'], true)) {
            $payrollFixedSalaryShortfallMode = 'full';
        }

        $db = DB::get();
        $stmt = $db->prepare("
            UPDATE companies
            SET payroll_lock_attendance_after_creation = ?,
                payroll_allow_manager = ?,
                payroll_default_salary_type = ?,

                payroll_component_fixed_salary = ?,
                payroll_component_hourly_wage = ?,
                payroll_component_company_bonus = ?,
                payroll_component_weekend_bonus = ?,
                payroll_component_holiday_bonus = ?,

                payroll_weekend_bonus_type = ?,
                payroll_weekend_bonus_value = ?,

                payroll_holiday_bonus_type = ?,
                payroll_holiday_bonus_value = ?,

                payroll_overlap_rule = ?,

                payroll_default_company_bonus_enabled = ?,
                payroll_default_company_bonus_source_type = ?,
                payroll_default_company_bonus_source_id = ?,
                payroll_default_company_bonus_calc_type = ?,
                payroll_default_company_bonus_value = ?,

                payroll_rounding_type = ?,
                payroll_count_overtime = ?,

                payroll_fixed_salary_shortfall_mode = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $payrollLockAttendance,
            $payrollAllowManager,
            $salaryType,

            $componentFixedSalary,
            $componentHourlyWage,
            $componentCompanyBonus,
            $componentWeekendBonus,
            $componentHolidayBonus,

            $weekendBonusType,
            $weekendBonusValue,

            $holidayBonusType,
            $holidayBonusValue,

            $payrollOverlapRule,

            $companyBonusEnabled,
            $companyBonusSourceType,
            $companyBonusSourceId,
            $companyBonusCalcType,
            $companyBonusValue,

            $roundingType,
            $countOvertime,

            $payrollFixedSalaryShortfallMode,
            Auth::companyId()
        ]);

        $_SESSION['flash_success'] = "Nastavení výplat uloženo.";
        header("Location: /settings");
        exit;
    }

    public function updateEconomicIndicators(): void
    {
        if (!Auth::canEditSettings()) {
            $this->forbid();
        }

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            header("Location: /settings");
            exit;
        }

        if (!\Core\Feature::enabled('economic_indicators')) {
            $_SESSION['flash_error'] = "Modul Ekonomické ukazatele není zapnutý.";
            header("Location: /settings");
            exit;
        }

        $economicIndicatorsAllowManager = isset($_POST['economic_indicators_allow_manager']) ? 1 : 0;

        $db = DB::get();
        $stmt = $db->prepare("
            UPDATE companies
            SET economic_indicators_allow_manager = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $economicIndicatorsAllowManager,
            Auth::companyId()
        ]);

        $_SESSION['flash_success'] = "Nastavení ekonomických ukazatelů uloženo.";
        header("Location: /settings");
        exit;
    }

    private function economicIndicatorDefinitions(): array
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT *
            FROM economic_indicator_definitions
            WHERE company_id = ?
            ORDER BY
                parent_id IS NULL DESC,
                parent_id ASC,
                sort_order ASC,
                id ASC
        ");
        $stmt->execute([Auth::companyId()]);
        return $stmt->fetchAll() ?: [];
    }

    private function economicIndicatorDefinitionById(int $id): ?array
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT *
            FROM economic_indicator_definitions
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$id, Auth::companyId()]);
        $row = $stmt->fetch();
        return $row ? (array)$row : null;
    }

    private function economicIndicatorGroupOptions(): array
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT id, name
            FROM economic_indicator_definitions
            WHERE company_id = ?
              AND type = 'group'
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([Auth::companyId()]);
        return $stmt->fetchAll() ?: [];
    }

    private function normalizeEconomicIndicatorType(string $value): string
    {
        return in_array($value, ['group', 'indicator'], true) ? $value : 'indicator';
    }

    private function normalizeEconomicIndicatorPeriodType(?string $value): ?string
    {
        $value = trim((string)$value);

        $allowed = ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'];

        return in_array($value, $allowed, true) ? $value : null;
    }

    public function createEconomicIndicatorDefinition(): void
    {
        if (!Auth::canEditSettings()) {
            $this->forbid();
        }

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            header("Location: /settings");
            exit;
        }

        if (!\Core\Feature::enabled('economic_indicators')) {
            $_SESSION['flash_error'] = "Modul Ekonomické ukazatele není zapnutý.";
            header("Location: /settings");
            exit;
        }

        $type = $this->normalizeEconomicIndicatorType((string)($_POST['type'] ?? 'indicator'));
        $name = trim((string)($_POST['name'] ?? ''));
        $unit = trim((string)($_POST['unit'] ?? ''));
        $parentId = (int)($_POST['parent_id'] ?? 0);
        $periodType = $this->normalizeEconomicIndicatorPeriodType($_POST['period_type'] ?? null);

        if ($name === '') {
            $_SESSION['flash_error'] = "Vyplň název položky.";
            header("Location: /settings");
            exit;
        }

        $parentIdOrNull = null;
        if ($parentId > 0) {
            $parent = $this->economicIndicatorDefinitionById($parentId);

            if (!$parent || ($parent['type'] ?? '') !== 'group') {
                $_SESSION['flash_error'] = "Nadřazená skupina nebyla nalezena.";
                header("Location: /settings");
                exit;
            }

            $parentIdOrNull = $parentId;
        }

        // pravidla pro periodu
        if ($type === 'group' && $periodType === null) {
            $_SESSION['flash_error'] = "Vyber období zadávání pro skupinu.";
            header("Location: /settings");
            exit;
        }

        if ($type === 'indicator' && $parentIdOrNull === null && $periodType === null) {
            $_SESSION['flash_error'] = "Vyber období zadávání pro samostatný ukazatel.";
            header("Location: /settings");
            exit;
        }

        // ukazatel pod skupinou má periodu převzatou ze skupiny
        if ($type === 'indicator' && $parentIdOrNull !== null) {
            $periodType = null;
        }

        $db = DB::get();

        $stmt = $db->prepare("
            SELECT COALESCE(MAX(sort_order), 0) + 1
            FROM economic_indicator_definitions
            WHERE company_id = ?
            AND ((parent_id IS NULL AND ? IS NULL) OR parent_id = ?)
        ");
        $stmt->execute([
            Auth::companyId(),
            $parentIdOrNull,
            $parentIdOrNull
        ]);
        $sortOrder = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("
            INSERT INTO economic_indicator_definitions (
                company_id, parent_id, type, name, unit, period_type, sort_order
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            Auth::companyId(),
            $parentIdOrNull,
            $type,
            $name,
            $type === 'indicator' && $unit !== '' ? $unit : null,
            $periodType,
            $sortOrder
        ]);

        $_SESSION['flash_success'] = "Položka ekonomických ukazatelů byla vytvořena.";
        header("Location: /settings");
        exit;
    }

    public function updateEconomicIndicatorDefinition(): void
    {
        if (!Auth::canEditSettings()) {
            $this->forbid();
        }

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            header("Location: /settings");
            exit;
        }

        if (!\Core\Feature::enabled('economic_indicators')) {
            $_SESSION['flash_error'] = "Modul Ekonomické ukazatele není zapnutý.";
            header("Location: /settings");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $item = $this->economicIndicatorDefinitionById($id);

        if (!$item) {
            $_SESSION['flash_error'] = "Položka nebyla nalezena.";
            header("Location: /settings");
            exit;
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $unit = trim((string)($_POST['unit'] ?? ''));
        $parentId = (int)($_POST['parent_id'] ?? 0);
        $periodType = $this->normalizeEconomicIndicatorPeriodType($_POST['period_type'] ?? null);

        if ($name === '') {
            $_SESSION['flash_error'] = "Vyplň název položky.";
            header("Location: /settings");
            exit;
        }

        $parentIdOrNull = null;
        if ($parentId > 0) {
            $parent = $this->economicIndicatorDefinitionById($parentId);

            if (!$parent || ($parent['type'] ?? '') !== 'group') {
                $_SESSION['flash_error'] = "Nadřazená skupina nebyla nalezena.";
                header("Location: /settings");
                exit;
            }

            if ((int)$parent['id'] === $id) {
                $_SESSION['flash_error'] = "Položka nemůže být sama sobě nadřazená.";
                header("Location: /settings");
                exit;
            }

            $parentIdOrNull = $parentId;
        }

        $type = (string)($item['type'] ?? 'indicator');

        if ($type === 'group' && $periodType === null) {
            $_SESSION['flash_error'] = "Vyber období zadávání pro skupinu.";
            header("Location: /settings");
            exit;
        }

        if ($type === 'indicator' && $parentIdOrNull === null && $periodType === null) {
            $_SESSION['flash_error'] = "Vyber období zadávání pro samostatný ukazatel.";
            header("Location: /settings");
            exit;
        }

        if ($type === 'indicator' && $parentIdOrNull !== null) {
            $periodType = null;
        }

        $db = DB::get();
        $stmt = $db->prepare("
            UPDATE economic_indicator_definitions
            SET parent_id = ?,
                name = ?,
                unit = ?,
                period_type = ?
            WHERE id = ?
            AND company_id = ?
        ");
        $stmt->execute([
            $parentIdOrNull,
            $name,
            $type === 'indicator' && $unit !== '' ? $unit : null,
            $periodType,
            $id,
            Auth::companyId()
        ]);

        $_SESSION['flash_success'] = "Položka ekonomických ukazatelů byla upravena.";
        header("Location: /settings");
        exit;
    }

    public function deleteEconomicIndicatorDefinition(): void
    {
        if (!Auth::canEditSettings()) {
            $this->forbid();
        }

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            header("Location: /settings");
            exit;
        }

        if (!\Core\Feature::enabled('economic_indicators')) {
            $_SESSION['flash_error'] = "Modul Ekonomické ukazatele není zapnutý.";
            header("Location: /settings");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $item = $this->economicIndicatorDefinitionById($id);

        if (!$item) {
            $_SESSION['flash_error'] = "Položka nebyla nalezena.";
            header("Location: /settings");
            exit;
        }

        $db = DB::get();
        $stmt = $db->prepare("
            DELETE FROM economic_indicator_definitions
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$id, Auth::companyId()]);

        $_SESSION['flash_success'] = "Položka ekonomických ukazatelů byla smazána.";
        header("Location: /settings");
        exit;
    }

    public function update()
    {
        if (!Auth::canEditSettings()) {
            $this->forbid();
        }

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            header("Location: /settings");
            exit;
        }

        $section = $_POST['_settings_section'] ?? 'company';

        if ($section === 'modules') {
            $servicesEnabled = isset($_POST['feature_services']);
            $vacationsEnabled = isset($_POST['feature_vacations']);
            $attendanceEnabled = isset($_POST['feature_attendance']);
            $payrollsEnabled = isset($_POST['feature_payrolls']);
            $economicIndicatorsEnabled = isset($_POST['feature_economic_indicators']);
            $wasteReportsEnabled = isset($_POST['feature_waste_reports']);
            $temperaturesEnabled = isset($_POST['feature_temperatures']);
            $sterilizationDryingEnabled = isset($_POST['feature_sterilization_drying']);
            $tasksEnabled = isset($_POST['feature_tasks']);

            \Core\Feature::setForCompany(
                \Core\Auth::companyId(),
                'services',
                $servicesEnabled,
                null
            );

            \Core\Feature::setForCompany(
                \Core\Auth::companyId(),
                'vacations',
                $vacationsEnabled,
                null
            );

            \Core\Feature::setForCompany(
                \Core\Auth::companyId(),
                'attendance',
                $attendanceEnabled,
                null
            );

            \Core\Feature::setForCompany(
                \Core\Auth::companyId(),
                'payrolls',
                $payrollsEnabled,
                null
            );

            \Core\Feature::setForCompany(
                \Core\Auth::companyId(),
                'economic_indicators',
                $economicIndicatorsEnabled,
                null
            );

            \Core\Feature::setForCompany(
                \Core\Auth::companyId(),
                'waste_reports',
                $wasteReportsEnabled,
                null
            );

            \Core\Feature::setForCompany(
                \Core\Auth::companyId(),
                'temperatures',
                $temperaturesEnabled,
                null
            );

            \Core\Feature::setForCompany(
                \Core\Auth::companyId(),
                'sterilization_drying',
                $sterilizationDryingEnabled,
                null
            );

            \Core\Feature::setForCompany(
                \Core\Auth::companyId(),
                'tasks',
                $tasksEnabled,
                null
            );

            $_SESSION['flash_success'] = "Moduly uloženy.";
            header("Location: /settings");
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $ico  = trim($_POST['ico'] ?? '');
        $dic  = trim($_POST['dic'] ?? '');
        $street = trim($_POST['street'] ?? '');
        $city   = trim($_POST['city'] ?? '');
        $zip    = trim($_POST['zip'] ?? '');

        if ($name === '' || $ico === '' || $street === '' || $city === '' || $zip === '') {
            $_SESSION['flash_error'] = "Vyplň všechna povinná pole.";
            header("Location: /settings");
            exit;
        }

        // pouze owner smí měnit přepínač
        $allowManager = null;
        if (Auth::role() === 'owner') {
            $allowManager = isset($_POST['allow_manager_settings']) ? 1 : 0;
        }

        $db = DB::get();

        if ($allowManager === null) {
            $stmt = $db->prepare("
                UPDATE companies
                SET name=?, ico=?, dic=?, street=?, city=?, zip=?
                WHERE id=?
            ");
            $stmt->execute([
                $name, $ico, ($dic !== '' ? $dic : null),
                $street, $city, $zip,
                Auth::companyId()
            ]);
        } else {
            $stmt = $db->prepare("
                UPDATE companies
                SET name=?, ico=?, dic=?, street=?, city=?, zip=?, allow_manager_settings=?
                WHERE id=?
            ");
            $stmt->execute([
                $name, $ico, ($dic !== '' ? $dic : null),
                $street, $city, $zip,
                $allowManager,
                Auth::companyId()
            ]);

            // aby se hned promítlo menu (bez re-login)
            Auth::setAllowManagerSettings($allowManager === 1);
        }

        // pokud používáš session cache firmy do topbaru:
        if (method_exists(Auth::class, 'setCompany')) {
            Auth::setCompany([
                'name' => $name,
                'ico' => $ico,
                'dic' => $dic,
                'street' => $street,
                'city' => $city,
                'zip' => $zip,
            ]);
        } else {
            $_SESSION['company_name'] = $name;
        }

        $_SESSION['flash_success'] = "Nastavení firmy uloženo.";
        header("Location: /settings");
        exit;
    }

    public function saveServicesCompanySettings()
    {
        // musí být přihlášený
        if (!\Core\Auth::check()) {
            http_response_code(403);
            exit('Forbidden');
        }

        // musí mít právo editovat settings (owner nebo manager když je povolen)
        if (!\Core\Auth::canEditSettings()) {
            $_SESSION['flash_error'] = "Nemáš oprávnění upravovat nastavení.";
            return $this->redirect('/settings');
        }

        // CSRF
        if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            return $this->redirect('/settings');
        }

        // hodnota checkboxu (když není zaškrtnutý, v POST vůbec není)
        $managerCanAssign = isset($_POST['manager_can_assign_services']) ? 1 : 0;

        try {
            $db = \Core\DB::get();

            $stmt = $db->prepare("
                UPDATE companies
                SET manager_can_assign_services = ?
                WHERE id = ?
            ");
            $stmt->execute([$managerCanAssign, \Core\Auth::companyId()]);

            $_SESSION['flash_success'] = "Nastavení modulu služeb bylo uloženo.";
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = "Chyba při ukládání: " . $e->getMessage();
        }

        return $this->redirect('/settings');
    }


    public function saveAttendanceSettings()
    {
        if (!\Core\Auth::canEditSettings()) {
            $this->forbid();
        }

        if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            header("Location: /settings");
            exit;
        }

        if (!\Core\Feature::enabled('attendance')) {
            $_SESSION['flash_error'] = "Modul Docházka není zapnutý.";
            header("Location: /settings");
            exit;
        }

        $rounding = (int)($_POST['attendance_rounding_minutes'] ?? 1);
        $allowBreak = isset($_POST['attendance_allow_break']) ? 1 : 0;

        $allowed = [1, 5, 10, 15, 30];
        if (!in_array($rounding, $allowed, true)) {
            $_SESSION['flash_error'] = "Neplatná hodnota zaokrouhlování.";
            header("Location: /settings");
            exit;
        }

        $db = \Core\DB::get();
        $stmt = $db->prepare("
            UPDATE companies
            SET attendance_rounding_minutes = ?,
                attendance_allow_break = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $rounding,
            $allowBreak,
            \Core\Auth::companyId()
        ]);

        $_SESSION['flash_success'] = "Nastavení docházky bylo uloženo.";
        header("Location: /settings");
        exit;
    }

    public function saveWasteReportsCompany()
    {
        if (!\Core\Auth::canEditSettings()) {
            $this->forbid();
        }

        if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            header("Location: /settings");
            exit;
        }

        if (!\Core\Feature::enabled('waste_reports')) {
            $_SESSION['flash_error'] = "Modul Hlášení odpadů není zapnutý.";
            header("Location: /settings");
            exit;
        }

        $managerCanView = isset($_POST['manager_can_view_waste_reports']) ? 1 : 0;

        $db = \Core\DB::get();
        $stmt = $db->prepare("
            UPDATE companies
            SET manager_can_view_waste_reports = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $managerCanView,
            \Core\Auth::companyId()
        ]);

        $_SESSION['flash_success'] = "Nastavení hlášení odpadů bylo uloženo.";
        header("Location: /settings");
        exit;
    }

    public function createWasteReportPlace()
    {
        if (!\Core\Auth::canEditSettings()) $this->forbid();

        if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            header("Location: /settings");
            exit;
        }

        $pharmacyName      = trim($_POST['pharmacy_name'] ?? '');
        $icp               = trim($_POST['icp'] ?? '');
        $street            = trim($_POST['street'] ?? '');
        $city              = trim($_POST['city'] ?? '');
        $zip               = trim($_POST['zip'] ?? '');
        $iczuj             = trim($_POST['iczuj'] ?? '');
        $regionalOffice    = trim($_POST['regional_office'] ?? '');
        $wasteHandlerIco   = trim($_POST['waste_handler_ico'] ?? '');
        $wasteFacilityIcz  = trim($_POST['waste_facility_icz'] ?? '');

        if ($pharmacyName === '') {
            $_SESSION['flash_error'] = "Vyplň název lékárny.";
            header("Location: /settings");
            exit;
        }

        $db = \Core\DB::get();
        $stmt = $db->prepare("
            INSERT INTO waste_report_places
            (
                company_id, pharmacy_name, icp, street, city, zip, iczuj,
                regional_office, waste_handler_ico, waste_facility_icz
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            \Core\Auth::companyId(),
            $pharmacyName,
            $icp !== '' ? $icp : null,
            $street !== '' ? $street : null,
            $city !== '' ? $city : null,
            $zip !== '' ? $zip : null,
            $iczuj !== '' ? $iczuj : null,
            $regionalOffice !== '' ? $regionalOffice : null,
            $wasteHandlerIco !== '' ? $wasteHandlerIco : null,
            $wasteFacilityIcz !== '' ? $wasteFacilityIcz : null,
        ]);

        $_SESSION['flash_success'] = "Místo svozu bylo vytvořeno.";
        header("Location: /settings");
        exit;
    }

    public function updateWasteReportPlace($id)
    {
        if (!\Core\Auth::canEditSettings()) $this->forbid();

        if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'message' => 'Neplatný formulář (CSRF).']);
            exit;
        }

        $db = \Core\DB::get();

        $stmt = $db->prepare("
            SELECT id
            FROM waste_report_places
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([(int)$id, \Core\Auth::companyId()]);
        $place = $stmt->fetch();

        if (!$place) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'Místo nebylo nalezeno.']);
            exit;
        }

        $pharmacyName      = trim($_POST['pharmacy_name'] ?? '');
        $icp               = trim($_POST['icp'] ?? '');
        $street            = trim($_POST['street'] ?? '');
        $city              = trim($_POST['city'] ?? '');
        $zip               = trim($_POST['zip'] ?? '');
        $iczuj             = trim($_POST['iczuj'] ?? '');
        $regionalOffice    = trim($_POST['regional_office'] ?? '');
        $wasteHandlerIco   = trim($_POST['waste_handler_ico'] ?? '');
        $wasteFacilityIcz  = trim($_POST['waste_facility_icz'] ?? '');

        if ($pharmacyName === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Vyplň název lékárny.']);
            exit;
        }

        $stmt = $db->prepare("
            UPDATE waste_report_places
            SET pharmacy_name = ?,
                icp = ?,
                street = ?,
                city = ?,
                zip = ?,
                iczuj = ?,
                regional_office = ?,
                waste_handler_ico = ?,
                waste_facility_icz = ?
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([
            $pharmacyName,
            $icp !== '' ? $icp : null,
            $street !== '' ? $street : null,
            $city !== '' ? $city : null,
            $zip !== '' ? $zip : null,
            $iczuj !== '' ? $iczuj : null,
            $regionalOffice !== '' ? $regionalOffice : null,
            $wasteHandlerIco !== '' ? $wasteHandlerIco : null,
            $wasteFacilityIcz !== '' ? $wasteFacilityIcz : null,
            (int)$id,
            \Core\Auth::companyId()
        ]);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
        exit;
    }

    public function deleteWasteReportPlace($id)
    {
        if (!\Core\Auth::canEditSettings()) $this->forbid();

        if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            header("Location: /settings");
            exit;
        }

        $db = \Core\DB::get();
        $stmt = $db->prepare("
            DELETE FROM waste_report_places
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([
            (int)$id,
            \Core\Auth::companyId()
        ]);

        $_SESSION['flash_success'] = "Místo svozu bylo smazáno.";
        header("Location: /settings");
        exit;
    }
}
