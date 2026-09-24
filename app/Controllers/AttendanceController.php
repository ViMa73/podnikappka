<?php
namespace Controllers;

use Core\Auth;
use Core\CSRF;
use Core\DB;
use Core\Feature;
use Core\AttendanceMonthLock;
use Core\AttendanceMonthLockException;

class AttendanceController
{
    private function redirect(string $to): void
    {
        header("Location: {$to}");
        exit;
    }

    private function requireAuth(): void
    {
        if (!Auth::check()) {
            $this->redirect('/');
        }
    }

    private function requireFeature(): void
    {
        if (!Feature::enabled('attendance')) {
            $_SESSION['flash_error'] = "Modul Docházka není zapnutý.";
            $this->redirect('/dashboard');
        }
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
        $stmt->execute([Auth::companyId()]);
        $row = $stmt->fetch();

        return $row ?: [
            'attendance_rounding_minutes' => 1,
            'attendance_allow_break' => 0,
        ];
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
        $stmt->execute([$userId, Auth::companyId()]);
        $value = $stmt->fetchColumn();

        $hours = (float)($value ?? 0);
        if ($hours < 0) {
            $hours = 0;
        }

        return (int)round($hours * 60);
    }

    private function normalizeMonth(?string $monthParam): string
    {
        if (!$monthParam || !preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
            return date('Y-m');
        }

        [$year, $month] = explode('-', $monthParam);
        $year = (int)$year;
        $month = (int)$month;

        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            return date('Y-m');
        }

        return sprintf('%04d-%02d', $year, $month);
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

    private function minutesToHuman(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
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
            $lunchToMin   = $this->timeToMinutes($lunchTo);

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
            $breakToMin   = $this->timeToMinutes($breakTo);

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

    public function index(): void
    {
        $this->requireAuth();
        $this->requireFeature();

        $db = DB::get();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $month = $this->normalizeMonth($_GET['month'] ?? null);

        $monthStart = new \DateTimeImmutable($month . '-01');
        $monthEnd = $monthStart->modify('last day of this month');
        $todayStr = date('Y-m-d');

        $company = $this->getCompanyAttendanceSettings();
        $roundingMinutes = (int)($company['attendance_rounding_minutes'] ?? 1);
        if (!in_array($roundingMinutes, [1, 5, 10, 15, 30], true)) {
            $roundingMinutes = 1;
        }

        $attendanceAllowBreak = (int)($company['attendance_allow_break'] ?? 0) === 1;
        $timeStepSeconds = $roundingMinutes * 60;

        $stmt = $db->prepare("
            SELECT *
            FROM attendance_records
            WHERE company_id = ?
              AND user_id = ?
              AND work_date BETWEEN ? AND ?
            ORDER BY work_date ASC
        ");
        $stmt->execute([
            Auth::companyId(),
            $userId,
            $monthStart->format('Y-m-d'),
            $monthEnd->format('Y-m-d'),
        ]);
        $rows = $stmt->fetchAll();

        $recordsByDate = [];
        $userWorkloadMinutes = $this->getUserWorkloadMinutes($userId);

        foreach ($rows as $row) {
            if (!empty($row['special_code']) && (int)($row['worked_minutes'] ?? 0) === 0) {
                $row['worked_minutes'] = $userWorkloadMinutes;
            }

            $recordsByDate[$row['work_date']] = $row;
        }

        $days = [];
        $cursor = $monthStart;
        while ($cursor <= $monthEnd) {
            $dateKey = $cursor->format('Y-m-d');
            $record = $recordsByDate[$dateKey] ?? null;

            $days[] = [
                'date' => $cursor,
                'date_key' => $dateKey,
                'is_future' => ($dateKey > $todayStr),
                'record' => $record,
            ];

            $cursor = $cursor->modify('+1 day');
        }

        $view = 'attendance/index';
        $title = 'Docházka';
        require __DIR__ . '/../Views/layout.php';
    }

    public function saveDay(): void
    {
        $this->requireAuth();
        $this->requireFeature();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            $this->redirect('/attendance');
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $companyId = (int)Auth::companyId();

        $workDate = trim($_POST['work_date'] ?? '');
        $monthParam = $this->normalizeMonth($_POST['_month'] ?? null);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $workDate)) {
            $_SESSION['flash_error'] = "Neplatné datum.";
            $this->redirect('/attendance?month=' . urlencode($monthParam));
        }

        $dateObj = \DateTimeImmutable::createFromFormat('Y-m-d', $workDate);
        if (!$dateObj) {
            $_SESSION['flash_error'] = "Neplatné datum.";
            $this->redirect('/attendance?month=' . urlencode($monthParam));
        }

        $todayStr = date('Y-m-d');
        if ($workDate > $todayStr) {
            $_SESSION['flash_error'] = "Docházku nelze zapisovat do budoucnosti.";
            $this->redirect('/attendance?month=' . urlencode($monthParam));
        }

        $specialCode = trim($_POST['special_code'] ?? '');
        $allowedSpecials = ['', 'D', 'O', 'PN', 'S'];
        if (!in_array($specialCode, $allowedSpecials, true)) {
            $_SESSION['flash_error'] = "Neplatná speciální hodnota.";
            $this->redirect('/attendance?month=' . urlencode($monthParam));
        }

        $lockYear = (int)date('Y', strtotime($workDate));
        $lockMonth = (int)date('n', strtotime($workDate));

        if (
            AttendanceMonthLock::isLocked($companyId, $lockYear, $lockMonth)
            && !AttendanceMonthLockException::hasException($companyId, $userId, $lockYear, $lockMonth)
        ) {
            $_SESSION['flash_error'] = "Docházka za toto období je uzamčena výplatou.";
            $this->redirect('/attendance?month=' . urlencode($monthParam));
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
                $this->redirect('/attendance?month=' . urlencode($monthParam));
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
            $workDate,
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

        $_SESSION['flash_success'] = "Docházka byla uložena.";
        $this->redirect('/attendance?month=' . urlencode($monthParam));
    }
}
