<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Core\DB;
use Core\Feature;
use Core\CSRF;
use Core\DashboardLayout;

class DashboardController extends Controller
{
    public function changeTheme()
    {
        DB::get()->prepare("
            UPDATE users SET theme = ? WHERE id = ?
        ")->execute([$_POST['theme'], Auth::user()]);

        $_SESSION['theme'] = $_POST['theme'];
        header("Location: /dashboard");
        exit;
    }

    private function requireAccess(): void
    {
        if (!Auth::check()) {
            header('Location: /');
            exit;
        }
    }

    private function companyId(): int
    {
        return (int) Auth::companyId();
    }

    private function userId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    private function jsonInput(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '{}', true);
        return is_array($data) ? $data : [];
    }

    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    private function requireJsonCsrf(array $data): void
    {
        if (!CSRF::check($data['_csrf'] ?? '')) {
            $this->jsonResponse(['ok' => false, 'message' => 'Neplatný formulář (CSRF).'], 419);
        }
    }

    private function getDashboardWidgetDefinitions(): array
    {
        return [
            'my_service' => [
                'label' => 'Moje služba',
                'span' => 4,
                'default_order' => 10,
                'view' => 'dashboard/widgets/my-service',
                'available' => Feature::enabled('services'),
            ],
            'remaining_vacation' => [
                'label' => 'Zbývající dovolená',
                'span' => 4,
                'default_order' => 20,
                'view' => 'dashboard/widgets/remaining-vacation',
                'available' => Feature::enabled('vacations'),
            ],
            'today_attendance' => [
                'label' => 'Dnešní docházka',
                'span' => 8,
                'default_order' => 30,
                'view' => 'dashboard/widgets/today-attendance',
                'available' => Feature::enabled('attendance'),
            ],
            'my_tasks' => [
                'label' => 'Moje úkoly',
                'span' => 4,
                'default_order' => 40,
                'view' => 'dashboard/widgets/my-tasks',
                'available' => Feature::enabled('tasks'),
            ],
            'today_temperatures' => [
                'label' => 'Dnešní teploty',
                'span' => 4,
                'default_order' => 50,
                'view' => 'dashboard/widgets/today-temperatures',
                'available' => Feature::enabled('temperatures'),
            ],

            'group_tasks' => [
                'label' => 'Skupinové úkoly',
                'span' => 8,
                'default_order' => 60,
                'view' => 'dashboard/widgets/group-tasks',
                'available' => Feature::enabled('tasks'),
            ],

            'my_attendance_month' => [
                'label' => 'Moje docházka',
                'span' => 4,
                'default_order' => 35,
                'view' => 'dashboard/widgets/my-attendance-month',
                'available' => Feature::enabled('attendance'),
            ],
            'economic_indicators' => [
                'label' => 'Ekonomické ukazatele',
                'span' => 8,
                'default_order' => 55,
                'view' => 'dashboard/widgets/economic-indicators',
                'available' => $this->canAccessEconomicIndicatorsWidget(),
            ],
        ];
    }

    private function computeWorkedMinutes(
        ?string $arrival,
        ?string $departure,
        ?string $lunchFrom,
        ?string $lunchTo,
        ?string $breakFrom,
        ?string $breakTo
    ): ?int {
        $arrival = trim((string)$arrival);
        $departure = trim((string)$departure);
        $lunchFrom = trim((string)$lunchFrom);
        $lunchTo = trim((string)$lunchTo);
        $breakFrom = trim((string)$breakFrom);
        $breakTo = trim((string)$breakTo);

        if ($arrival === '' && $departure === '') {
            return 0;
        }

        if ($arrival === '' || $departure === '') {
            return null;
        }

        $arrivalMin = $this->timeToMinutes($arrival);
        $departureMin = $this->timeToMinutes($departure);

        if ($arrivalMin === null || $departureMin === null) {
            return null;
        }

        if ($departureMin < $arrivalMin) {
            return null;
        }

        $worked = $departureMin - $arrivalMin;

        if (($lunchFrom === '' && $lunchTo !== '') || ($lunchFrom !== '' && $lunchTo === '')) {
            return null;
        }

        if ($lunchFrom !== '' && $lunchTo !== '') {
            $lunchFromMin = $this->timeToMinutes($lunchFrom);
            $lunchToMin = $this->timeToMinutes($lunchTo);

            if ($lunchFromMin === null || $lunchToMin === null || $lunchToMin < $lunchFromMin) {
                return null;
            }

            $worked -= ($lunchToMin - $lunchFromMin);
        }

        if (($breakFrom === '' && $breakTo !== '') || ($breakFrom !== '' && $breakTo === '')) {
            return null;
        }

        if ($breakFrom !== '' && $breakTo !== '') {
            $breakFromMin = $this->timeToMinutes($breakFrom);
            $breakToMin = $this->timeToMinutes($breakTo);

            if ($breakFromMin === null || $breakToMin === null || $breakToMin < $breakFromMin) {
                return null;
            }

            $worked -= ($breakToMin - $breakFromMin);
        }

        if ($worked < 0) {
            return null;
        }

        return $worked;
    }

    private function getCompanyAttendanceSettings(): array
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT attendance_rounding_minutes, attendance_allow_break
            FROM companies
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$this->companyId()]);
        $row = $stmt->fetch();

        return $row ?: [
            'attendance_rounding_minutes' => 1,
            'attendance_allow_break' => 0,
        ];
    }

    private function canAccessEconomicIndicatorsWidget(): bool
    {
        if (!Feature::enabled('economic_indicators')) {
            return false;
        }

        $role = (string)\Core\Auth::role();

        if ($role === 'owner') {
            return true;
        }

        if ($role === 'manager') {
            $db = DB::get();
            $stmt = $db->prepare("
                SELECT economic_indicators_allow_manager
                FROM companies
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([$this->companyId()]);
            $company = $stmt->fetch() ?: [];

            return (int)($company['economic_indicators_allow_manager'] ?? 0) === 1;
        }

        return false;
    }

    private function getEconomicIndicatorWidgetStructures(): array
    {
        if (!$this->canAccessEconomicIndicatorsWidget()) {
            return [];
        }

        $db = DB::get();

        $stmt = $db->prepare("
            SELECT
                id,
                name
            FROM economic_indicator_definitions
            WHERE company_id = ?
              AND parent_id IS NULL
            ORDER BY sort_order ASC, name ASC, id ASC
        ");
        $stmt->execute([$this->companyId()]);

        return $stmt->fetchAll() ?: [];
    }

    private function getUserWorkloadMinutes(int $userId): int
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT workload_hours
            FROM users
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId, $this->companyId()]);
        $value = $stmt->fetchColumn();

        $hours = (float)($value ?? 0);
        if ($hours < 0) {
            $hours = 0;
        }

        return (int)round($hours * 60);
    }

    private function timeToMinutes(?string $time): ?int
    {
        if ($time === null) return null;

        $time = trim($time);
        if ($time === '') return null;

        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $time, $m)) {
            return ((int)$m[1] * 60) + (int)$m[2];
        }

        return null;
    }

    private function minutesToHuman(int $minutes, bool $withSign = false): string
    {
        $sign = '';

        if ($minutes < 0) {
            $sign = '-';
            $minutes = abs($minutes);
        } elseif ($withSign && $minutes > 0) {
            $sign = '+';
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return $sign . sprintf('%02d:%02d', $hours, $mins);
    }

    private function getCzechMonthLabel(\DateTimeInterface $date): string
    {
        $months = [
            1 => 'leden',
            2 => 'únor',
            3 => 'březen',
            4 => 'duben',
            5 => 'květen',
            6 => 'červen',
            7 => 'červenec',
            8 => 'srpen',
            9 => 'září',
            10 => 'říjen',
            11 => 'listopad',
            12 => 'prosinec',
        ];

        $month = (int)$date->format('n');
        $year = $date->format('Y');

        return ($months[$month] ?? '') . ' ' . $year;
    }

    private function countWeekdaysInRange(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        if ($to < $from) {
            return 0;
        }

        $count = 0;
        $current = $from;

        while ($current <= $to) {
            $dayOfWeek = (int)$current->format('N'); // 1 = po, 7 = ne
            if ($dayOfWeek <= 5) {
                $count++;
            }

            $current = $current->modify('+1 day');
        }

        return $count;
    }

    private function buildMyAttendanceMonthWidgetData(): ?array
    {
        $userId = $this->userId();
        $companyId = $this->companyId();
        $db = DB::get();

        $workloadMinutes = $this->getUserWorkloadMinutes($userId);
        if ($workloadMinutes <= 0) {
            return null;
        }

        $today = new \DateTimeImmutable('today');
        $monthStart = $today->modify('first day of this month');

        $expectedDays = $this->countWeekdaysInRange($monthStart, $today);
        $expectedMinutes = $expectedDays * $workloadMinutes;

        $stmt = $db->prepare("
            SELECT COALESCE(SUM(worked_minutes), 0)
            FROM attendance_records
            WHERE company_id = ?
              AND user_id = ?
              AND work_date BETWEEN ? AND ?
        ");
        $stmt->execute([
            $companyId,
            $userId,
            $monthStart->format('Y-m-d'),
            $today->format('Y-m-d'),
        ]);

        $workedMinutes = (int)($stmt->fetchColumn() ?? 0);
        $balanceMinutes = $workedMinutes - $expectedMinutes;

        $toleranceMinutes = 60;

        if ($balanceMinutes > $toleranceMinutes) {
            $status = 'plus';
            $statusText = 'Jsi nad plánem';
        } elseif ($balanceMinutes < -$toleranceMinutes) {
            $status = 'minus';
            $statusText = 'Jsi pod plánem';
        } else {
            $status = 'ok';
            $statusText = 'Jsi přibližně na plánu';
        }

        return [
            'month_label' => $this->getCzechMonthLabel($today),
            'worked_minutes' => $workedMinutes,
            'expected_minutes' => $expectedMinutes,
            'balance_minutes' => $balanceMinutes,
            'worked_label' => $this->minutesToHuman($workedMinutes),
            'expected_label' => $this->minutesToHuman($expectedMinutes),
            'balance_label' => $this->minutesToHuman($balanceMinutes, true),
            'status' => $status,
            'status_text' => $statusText,
            'expected_days' => $expectedDays,
            'workload_label' => $this->minutesToHuman($workloadMinutes),
            'today_label' => $today->format('j.n.Y'),
        ];
    }

    public function index()
    {
        $this->requireAccess();

        $db = DB::get();
        $userId = $this->userId();
        $companyId = $this->companyId();

        $stmt = $db->prepare("
            SELECT vacation_hours_year
            FROM users
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId, $companyId]);
        $me = $stmt->fetch() ?: [];

        $dashboardWidgetDefinitions = $this->getDashboardWidgetDefinitions();

        $dashboardWidgetControls = array_values(array_map(
            static function (string $key, array $def): array {
                return [
                    'key' => $key,
                    'label' => (string)$def['label'],
                    'available' => !empty($def['available']),
                    'span' => (int)$def['span'],
                ];
            },
            array_keys($dashboardWidgetDefinitions),
            $dashboardWidgetDefinitions
        ));

        $dashboardWidgets = DashboardLayout::loadForUser(
            $companyId,
            $userId,
            $dashboardWidgetDefinitions
        );

        /*
        |----------------------------------------------------------------------
        | Data widgetu: Moje služba
        |----------------------------------------------------------------------
        */
        $showMyServiceWidget = Feature::enabled('services');
        $myServicesToday = [];
        $myServicesTomorrow = [];

        if ($showMyServiceWidget) {
            $today = (new \DateTimeImmutable('now'))->format('Y-m-d');
            $tomorrow = (new \DateTimeImmutable('tomorrow'))->format('Y-m-d');

            $stmt = $db->prepare("
                SELECT a.service_date,
                       p.name AS place_name,
                       p.description
                FROM service_assignments a
                JOIN service_places p ON p.id = a.place_id
                WHERE a.company_id = ?
                  AND a.user_id = ?
                  AND a.service_date IN (?, ?)
                ORDER BY a.service_date ASC, p.name ASC
            ");
            $stmt->execute([$companyId, $userId, $today, $tomorrow]);
            $rows = $stmt->fetchAll();

            foreach ($rows as $row) {
                if (($row['service_date'] ?? '') === $today) {
                    $myServicesToday[] = $row;
                } elseif (($row['service_date'] ?? '') === $tomorrow) {
                    $myServicesTomorrow[] = $row;
                }
            }
        }

        /*
        |----------------------------------------------------------------------
        | Data widgetu: Zbývající dovolená
        |----------------------------------------------------------------------
        */
        $showVacationWidget = Feature::enabled('vacations');
        $currentYear = (int)date('Y');
        $vacationEntitlementYear = (float)($me['vacation_hours_year'] ?? 0);
        $vacationUsedYear = 0.0;
        $vacationRemainingYear = 0.0;

        if ($showVacationWidget) {
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(total_hours), 0) AS used_hours
                FROM vacations
                WHERE company_id = ?
                  AND user_id = ?
                  AND YEAR(date_from) = ?
            ");
            $stmt->execute([$companyId, $userId, $currentYear]);

            $vacationUsedYear = (float)($stmt->fetchColumn() ?? 0);
            $vacationRemainingYear = $vacationEntitlementYear - $vacationUsedYear;

            if ($vacationRemainingYear < 0) {
                $vacationRemainingYear = 0;
            }
        }

        /*
        |----------------------------------------------------------------------
        | Data widgetu: Dnešní docházka
        |----------------------------------------------------------------------
        */
        $showAttendanceWidget = Feature::enabled('attendance');
        $attendanceAllowBreak = false;
        $dashboardAttendance = null;
        $dashboardAttendancePrev = null;
        $dashboardAttendanceTimeStepSeconds = 60;
        $dashboardAttendanceUserWorkloadMinutes = 0;

        if ($showAttendanceWidget) {
            $attendanceSettings = $this->getCompanyAttendanceSettings();
            $attendanceAllowBreak = (int)($attendanceSettings['attendance_allow_break'] ?? 0) === 1;

            $roundingMinutes = (int)($attendanceSettings['attendance_rounding_minutes'] ?? 1);
            if (!in_array($roundingMinutes, [1, 5, 10, 15, 30], true)) {
                $roundingMinutes = 1;
            }
            $dashboardAttendanceTimeStepSeconds = $roundingMinutes * 60;

            $todayStr = date('Y-m-d');

            $stmt = $db->prepare("
                SELECT *
                FROM attendance_records
                WHERE company_id = ? AND user_id = ? AND work_date = ?
                LIMIT 1
            ");
            $stmt->execute([$companyId, $userId, $todayStr]);
            $dashboardAttendance = $stmt->fetch() ?: null;

            $stmt = $db->prepare("
                SELECT *
                FROM attendance_records
                WHERE company_id = ? AND user_id = ? AND work_date < ? AND (special_code IS NULL OR special_code = '')
                ORDER BY work_date DESC
                LIMIT 1
            ");
            $stmt->execute([$companyId, $userId, $todayStr]);
            $dashboardAttendancePrev = $stmt->fetch() ?: null;

            $dashboardAttendanceUserWorkloadMinutes = $this->getUserWorkloadMinutes($userId);

            if (
                $dashboardAttendance &&
                !empty($dashboardAttendance['special_code']) &&
                (int)($dashboardAttendance['worked_minutes'] ?? 0) === 0
            ) {
                $dashboardAttendance['worked_minutes'] = $dashboardAttendanceUserWorkloadMinutes;
            }
        }


        /*
        |----------------------------------------------------------------------
        | Data widgetu: Moje docházka
        |----------------------------------------------------------------------
        */
        $myAttendanceMonthWidget = null;

        if (Feature::enabled('attendance')) {
            $myAttendanceMonthWidget = $this->buildMyAttendanceMonthWidgetData();
        }


        /*
        |----------------------------------------------------------------------
        | Data widgetu: Ekonomické ukazatele
        |----------------------------------------------------------------------
        */
        $showEconomicIndicatorsWidget = $this->canAccessEconomicIndicatorsWidget();
        $economicIndicatorWidgetStructures = [];

        if ($showEconomicIndicatorsWidget) {
            $economicIndicatorWidgetStructures = $this->getEconomicIndicatorWidgetStructures();
        }

        $view = 'dashboard';
        $title = 'Dashboard';
        require __DIR__ . '/../Views/layout.php';
    }

    public function saveLayout(): void
    {
        $this->requireAccess();

        $data = $this->jsonInput();
        $this->requireJsonCsrf($data);

        $definitions = $this->getDashboardWidgetDefinitions();
        $widgets = $data['widgets'] ?? [];

        if (!is_array($widgets)) {
            $this->jsonResponse(['ok' => false, 'message' => 'Neplatná data pořadí.'], 422);
        }

        DashboardLayout::saveOrder(
            $this->companyId(),
            $this->userId(),
            $definitions,
            $widgets
        );

        $this->jsonResponse(['ok' => true]);
    }

    public function setWidgetEnabled(): void
    {
        $this->requireAccess();

        $data = $this->jsonInput();
        $this->requireJsonCsrf($data);

        $definitions = $this->getDashboardWidgetDefinitions();
        $widgetKey = trim((string)($data['widget_key'] ?? ''));
        $enabled = !empty($data['enabled']);

        try {
            DashboardLayout::setEnabled(
                $this->companyId(),
                $this->userId(),
                $definitions,
                $widgetKey,
                $enabled
            );
        } catch (\Throwable $e) {
            $this->jsonResponse(['ok' => false, 'message' => 'Nepodařilo se uložit nastavení widgetu.'], 422);
        }

        $this->jsonResponse(['ok' => true]);
    }

    public function resetLayout(): void
    {
        $this->requireAccess();

        $data = $this->jsonInput();
        $this->requireJsonCsrf($data);

        $definitions = $this->getDashboardWidgetDefinitions();

        DashboardLayout::reset(
            $this->companyId(),
            $this->userId(),
            $definitions
        );

        $this->jsonResponse(['ok' => true]);
    }

    public function saveTodayAttendance(): void
    {
        if (!Auth::check()) {
            header("Location: /");
            exit;
        }

        if (!Feature::enabled('attendance')) {
            $_SESSION['flash_error'] = "Modul Docházka není zapnutý.";
            header("Location: /dashboard");
            exit;
        }

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            header("Location: /dashboard");
            exit;
        }

        $userId = $this->userId();
        $companyId = $this->companyId();
        $todayStr = date('Y-m-d');

        $workDate = trim($_POST['work_date'] ?? '');
        if ($workDate !== $todayStr) {
            $_SESSION['flash_error'] = "Na dashboardu lze upravit pouze dnešní docházku.";
            header("Location: /dashboard");
            exit;
        }

        $specialCode = trim($_POST['special_code'] ?? '');
        $allowedSpecials = ['', 'D', 'O', 'PN', 'S'];
        if (!in_array($specialCode, $allowedSpecials, true)) {
            $_SESSION['flash_error'] = "Neplatná speciální hodnota.";
            header("Location: /dashboard");
            exit;
        }

        $company = $this->getCompanyAttendanceSettings();
        $attendanceAllowBreak = (int)($company['attendance_allow_break'] ?? 0) === 1;

        $arrivalTime   = trim($_POST['arrival_time'] ?? '');
        $departureTime = trim($_POST['departure_time'] ?? '');
        $lunchFrom     = trim($_POST['lunch_from'] ?? '');
        $lunchTo       = trim($_POST['lunch_to'] ?? '');
        $breakFrom     = trim($_POST['break_from'] ?? '');
        $breakTo       = trim($_POST['break_to'] ?? '');
        $note          = trim($_POST['note'] ?? '');

        if (!$attendanceAllowBreak) {
            $breakFrom = '';
            $breakTo = '';
        }

        if ($specialCode !== '') {
            $arrivalTime = '';
            $departureTime = '';
            $lunchFrom = '';
            $lunchTo = '';
            $breakFrom = '';
            $breakTo = '';

            $workedMinutes = $this->getUserWorkloadMinutes($userId);
        } else {
            $workedMinutes = $this->computeWorkedMinutes(
                $arrivalTime !== '' ? $arrivalTime : null,
                $departureTime !== '' ? $departureTime : null,
                $lunchFrom !== '' ? $lunchFrom : null,
                $lunchTo !== '' ? $lunchTo : null,
                $breakFrom !== '' ? $breakFrom : null,
                $breakTo !== '' ? $breakTo : null
            );

            if (
                ($arrivalTime !== '' || $departureTime !== '' || $lunchFrom !== '' || $lunchTo !== '' || $breakFrom !== '' || $breakTo !== '')
                && $workedMinutes === null
            ) {
                $_SESSION['flash_error'] = "Časy nejsou zadané správně.";
                header("Location: /dashboard");
                exit;
            }

            if ($workedMinutes === null) {
                $workedMinutes = 0;
            }
        }

        $db = DB::get();

        $stmt = $db->prepare("
            INSERT INTO attendance_records (
                company_id, user_id, work_date, special_code,
                arrival_time, departure_time, lunch_from, lunch_to,
                break_from, break_to, note, worked_minutes
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                special_code = VALUES(special_code),
                arrival_time = VALUES(arrival_time),
                departure_time = VALUES(departure_time),
                lunch_from = VALUES(lunch_from),
                lunch_to = VALUES(lunch_to),
                break_from = VALUES(break_from),
                break_to = VALUES(break_to),
                note = VALUES(note),
                worked_minutes = VALUES(worked_minutes)
        ");
        $stmt->execute([
            $companyId,
            $userId,
            $todayStr,
            $specialCode !== '' ? $specialCode : null,
            $arrivalTime !== '' ? $arrivalTime . ':00' : null,
            $departureTime !== '' ? $departureTime . ':00' : null,
            $lunchFrom !== '' ? $lunchFrom . ':00' : null,
            $lunchTo !== '' ? $lunchTo . ':00' : null,
            $breakFrom !== '' ? $breakFrom . ':00' : null,
            $breakTo !== '' ? $breakTo . ':00' : null,
            $note !== '' ? $note : null,
            $workedMinutes,
        ]);

        $_SESSION['flash_success'] = "Dnešní docházka byla uložena.";
        header("Location: /dashboard");
        exit;
    }
}
