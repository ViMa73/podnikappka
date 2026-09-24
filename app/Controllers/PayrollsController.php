<?php

namespace Controllers;

use Core\AttendanceMonthLock;
use Core\AttendanceMonthLockException;
use Core\Auth;
use Core\CSRF;
use Core\DB;
use Core\Feature;
use Services\PayrollBuilder;

class PayrollsController
{
    private function requireAccess(): void
    {
        if (!Auth::check()) {
            header('Location: /');
            exit;
        }
    }

    private function requireFeature(): void
    {
        if (!Feature::enabled('payrolls')) {
            http_response_code(404);
            exit('Modul Výplaty není zapnutý.');
        }
    }

    private function companyId(): int
    {
        return (int)Auth::companyId();
    }

    private function userId(): int
    {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    private function canManage(): bool
    {
        if (Auth::role() === 'owner') {
            return true;
        }

        if (Auth::role() === 'manager') {
            $db = DB::get();
            $stmt = $db->prepare("
                SELECT payroll_allow_manager
                FROM companies
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([$this->companyId()]);
            return (int)$stmt->fetchColumn() === 1;
        }

        return false;
    }

    private function canSeeOwnPayrolls(): bool
    {
        return true;
    }

    private function getCompany(): array
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT *
            FROM companies
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$this->companyId()]);
        return $stmt->fetch() ?: [];
    }

    public function index(): void
    {
        $this->requireAccess();
        $this->requireFeature();

        $db = DB::get();
        $canManage = $this->canManage();

        if ($canManage) {
            $stmt = $db->prepare("
                SELECT p.*,
                       COUNT(i.id) AS items_count
                FROM payroll_periods p
                LEFT JOIN payroll_items i ON i.payroll_period_id = p.id
                WHERE p.company_id = ?
                GROUP BY p.id
                ORDER BY p.year DESC, p.month DESC
            ");
            $stmt->execute([$this->companyId()]);
            $periods = $stmt->fetchAll() ?: [];
        } else {
            $stmt = $db->prepare("
                SELECT p.*, i.gross_total_amount, i.personal_bonus_amount, i.note
                FROM payroll_periods p
                JOIN payroll_items i ON i.payroll_period_id = p.id
                WHERE p.company_id = ?
                  AND i.user_id = ?
                ORDER BY p.year DESC, p.month DESC
            ");
            $stmt->execute([$this->companyId(), $this->userId()]);
            $periods = $stmt->fetchAll() ?: [];
        }

        $view = 'payrolls/index';
        $title = 'Výplaty';
        require __DIR__ . '/../Views/layout.php';
    }

    public function create(): void
    {
        $this->requireAccess();
        $this->requireFeature();

        if (!$this->canManage()) {
            http_response_code(403);
            exit('Nemáš oprávnění.');
        }

        $view = 'payrolls/create';
        $title = 'Nová výplata';
        require __DIR__ . '/../Views/layout.php';
    }

    public function store(): void
    {
        $this->requireAccess();
        $this->requireFeature();

        if (!$this->canManage()) {
            http_response_code(403);
            exit('Nemáš oprávnění.');
        }

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            header('Location: /payrolls/create');
            exit;
        }

        $year = (int)($_POST['year'] ?? 0);
        $month = (int)($_POST['month'] ?? 0);

        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            $_SESSION['flash_error'] = 'Vyber platné období.';
            header('Location: /payrolls/create');
            exit;
        }

        $db = DB::get();
        $stmt = $db->prepare("
            SELECT id
            FROM payroll_periods
            WHERE company_id = ?
              AND year = ?
              AND month = ?
            LIMIT 1
        ");
        $stmt->execute([$this->companyId(), $year, $month]);

        $existingId = (int)$stmt->fetchColumn();
        if ($existingId > 0) {
            header('Location: /payrolls/' . $existingId);
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO payroll_periods (company_id, year, month, status, attendance_locked, created_by)
            VALUES (?, ?, ?, 'draft', 0, ?)
        ");
        $stmt->execute([$this->companyId(), $year, $month, $this->userId()]);
        $periodId = (int)$db->lastInsertId();

        $builder = new PayrollBuilder($this->companyId(), $year, $month);
        $builder->buildAllForPeriod($periodId);

        $company = $this->getCompany();
        $lockAttendance = (int)($company['payroll_lock_attendance_after_creation'] ?? 1) === 1;

        if ($lockAttendance) {
            AttendanceMonthLock::lock($this->companyId(), $year, $month, $periodId, $this->userId());

            $stmt = $db->prepare("
                UPDATE payroll_periods
                SET attendance_locked = 1
                WHERE id = ? AND company_id = ?
            ");
            $stmt->execute([$periodId, $this->companyId()]);
        }

        $_SESSION['flash_success'] = 'Výplata byla vytvořena jako rozpracovaná.';
        header('Location: /payrolls/' . $periodId);
        exit;
    }

    public function show($id): void
    {
        $this->requireAccess();
        $this->requireFeature();

        $periodId = (int)$id;
        $db = DB::get();

        $stmt = $db->prepare("
            SELECT *
            FROM payroll_periods
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$periodId, $this->companyId()]);
        $period = $stmt->fetch();

        if (!$period) {
            http_response_code(404);
            exit('Výplata nebyla nalezena.');
        }

        $canManage = $this->canManage();

        if ($canManage) {
            $stmt = $db->prepare("
                SELECT *
                FROM payroll_items
                WHERE payroll_period_id = ?
                ORDER BY user_name_snapshot ASC
            ");
            $stmt->execute([$periodId]);
            $items = $stmt->fetchAll() ?: [];
        } else {
            $stmt = $db->prepare("
                SELECT *
                FROM payroll_items
                WHERE payroll_period_id = ?
                  AND user_id = ?
                LIMIT 1
            ");
            $stmt->execute([$periodId, $this->userId()]);
            $row = $stmt->fetch();
            $items = $row ? [$row] : [];
        }

        $daysByItem = [];
        if (!empty($items)) {
            $itemIds = array_map(static fn($x) => (int)$x['id'], $items);
            $placeholders = implode(',', array_fill(0, count($itemIds), '?'));

            $stmt = $db->prepare("
                SELECT *
                FROM payroll_item_days
                WHERE payroll_item_id IN ($placeholders)
                ORDER BY day_date ASC, id ASC
            ");
            $stmt->execute($itemIds);
            $days = $stmt->fetchAll() ?: [];

            foreach ($days as $day) {
                $daysByItem[(int)$day['payroll_item_id']][] = $day;
            }
        }

        $view = 'payrolls/show';
        $title = 'Detail výplaty';
        require __DIR__ . '/../Views/layout.php';
    }

    public function updateItem($id): void
    {
        $this->requireAccess();
        $this->requireFeature();

        if (!$this->canManage()) {
            http_response_code(403);
            exit('Nemáš oprávnění.');
        }

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            header('Location: /payrolls/' . (int)$id);
            exit;
        }

        $periodId = (int)$id;
        $itemId = (int)($_POST['item_id'] ?? 0);
        $personalBonus = trim((string)($_POST['personal_bonus_amount'] ?? '0'));
        $note = trim((string)($_POST['note'] ?? ''));

        $personalBonus = str_replace(',', '.', $personalBonus);
        if (!is_numeric($personalBonus)) {
            $_SESSION['flash_error'] = 'Osobní ohodnocení musí být číslo.';
            header('Location: /payrolls/' . $periodId);
            exit;
        }

        $db = DB::get();
        $stmt = $db->prepare("
            SELECT *
            FROM payroll_periods
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$periodId, $this->companyId()]);
        $period = $stmt->fetch();

        if (!$period || $period['status'] !== 'draft') {
            $_SESSION['flash_error'] = 'Upravovat lze jen rozpracovanou výplatu.';
            header('Location: /payrolls/' . $periodId);
            exit;
        }

        $stmt = $db->prepare("
            SELECT *
            FROM payroll_items
            WHERE id = ? AND payroll_period_id = ?
            LIMIT 1
        ");
        $stmt->execute([$itemId, $periodId]);
        $item = $stmt->fetch();

        if (!$item) {
            $_SESSION['flash_error'] = 'Položka výplaty nebyla nalezena.';
            header('Location: /payrolls/' . $periodId);
            exit;
        }

        $grossTotal = (float)$item['base_salary_amount']
            + (float)$item['company_bonus_amount']
            + (float)$item['weekend_bonus_amount']
            + (float)$item['holiday_bonus_amount']
            + (float)$personalBonus;

        $stmt = $db->prepare("
            UPDATE payroll_items
            SET personal_bonus_amount = ?,
                gross_total_amount = ?,
                note = ?
            WHERE id = ?
              AND payroll_period_id = ?
        ");
        $stmt->execute([
            (float)$personalBonus,
            round($grossTotal, 2),
            $note !== '' ? $note : null,
            $itemId,
            $periodId
        ]);

        $_SESSION['flash_success'] = 'Položka výplaty byla upravena.';
        header('Location: /payrolls/' . $periodId);
        exit;
    }

    public function allowAttendanceEdit($id): void
    {
        $this->requireAccess();
        $this->requireFeature();

        if (!$this->canManage()) {
            http_response_code(403);
            exit('Nemáš oprávnění.');
        }

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            header('Location: /payrolls/' . (int)$id);
            exit;
        }

        $periodId = (int)$id;
        $itemId = (int)($_POST['item_id'] ?? 0);

        $db = DB::get();

        $stmt = $db->prepare("
            SELECT *
            FROM payroll_periods
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$periodId, $this->companyId()]);
        $period = $stmt->fetch();

        if (!$period || $period['status'] !== 'draft') {
            $_SESSION['flash_error'] = 'Docházku lze otevřít jen u rozpracované výplaty.';
            header('Location: /payrolls/' . $periodId);
            exit;
        }

        $stmt = $db->prepare("
            SELECT user_id
            FROM payroll_items
            WHERE id = ? AND payroll_period_id = ?
            LIMIT 1
        ");
        $stmt->execute([$itemId, $periodId]);
        $userId = (int)$stmt->fetchColumn();

        if ($userId <= 0) {
            $_SESSION['flash_error'] = 'Položka výplaty nebyla nalezena.';
            header('Location: /payrolls/' . $periodId);
            exit;
        }

        AttendanceMonthLockException::allow(
            $this->companyId(),
            $userId,
            (int)$period['year'],
            (int)$period['month'],
            $periodId,
            $this->userId()
        );

        $_SESSION['flash_success'] = 'Docházka byla pro tohoto zaměstnance dočasně otevřena.';
        header('Location: /payrolls/' . $periodId);
        exit;
    }

    public function recalculateItem($id): void
    {
        $this->requireAccess();
        $this->requireFeature();

        if (!$this->canManage()) {
            http_response_code(403);
            exit('Nemáš oprávnění.');
        }

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            header('Location: /payrolls/' . (int)$id);
            exit;
        }

        $periodId = (int)$id;
        $itemId = (int)($_POST['item_id'] ?? 0);

        $db = DB::get();

        $stmt = $db->prepare("
            SELECT *
            FROM payroll_periods
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$periodId, $this->companyId()]);
        $period = $stmt->fetch();

        if (!$period || $period['status'] !== 'draft') {
            $_SESSION['flash_error'] = 'Přepočet lze udělat jen u rozpracované výplaty.';
            header('Location: /payrolls/' . $periodId);
            exit;
        }

        $stmt = $db->prepare("
            SELECT *
            FROM payroll_items
            WHERE id = ? AND payroll_period_id = ?
            LIMIT 1
        ");
        $stmt->execute([$itemId, $periodId]);
        $item = $stmt->fetch();

        if (!$item) {
            $_SESSION['flash_error'] = 'Položka výplaty nebyla nalezena.';
            header('Location: /payrolls/' . $periodId);
            exit;
        }

        $builder = new PayrollBuilder(
            $this->companyId(),
            (int)$period['year'],
            (int)$period['month']
        );

        $builder->rebuildSingleItem(
            $periodId,
            (int)$item['user_id'],
            (float)$item['personal_bonus_amount'],
            $item['note'] !== null ? (string)$item['note'] : null
        );

        AttendanceMonthLockException::remove(
            $this->companyId(),
            (int)$item['user_id'],
            (int)$period['year'],
            (int)$period['month']
        );

        $_SESSION['flash_success'] = 'Položka výplaty byla znovu dopočítána z docházky.';
        header('Location: /payrolls/' . $periodId);
        exit;
    }

    public function approve($id): void
    {
        $this->requireAccess();
        $this->requireFeature();

        if (!$this->canManage()) {
            http_response_code(403);
            exit('Nemáš oprávnění.');
        }

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            header('Location: /payrolls/' . (int)$id);
            exit;
        }

        $periodId = (int)$id;
        $db = DB::get();

        $stmt = $db->prepare("
            SELECT *
            FROM payroll_periods
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$periodId, $this->companyId()]);
        $period = $stmt->fetch();

        if (!$period) {
            $_SESSION['flash_error'] = 'Výplata nebyla nalezena.';
            header('Location: /payrolls');
            exit;
        }

        $stmt = $db->prepare("
            UPDATE payroll_periods
            SET status = 'approved',
                approved_by = ?,
                approved_at = NOW()
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$this->userId(), $periodId, $this->companyId()]);

        $_SESSION['flash_success'] = 'Výplata byla schválena.';
        header('Location: /payrolls/' . $periodId);
        exit;
    }

    public function reopen($id): void
    {
        $this->requireAccess();
        $this->requireFeature();

        if (!$this->canManage()) {
            http_response_code(403);
            exit('Nemáš oprávnění.');
        }

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            header('Location: /payrolls/' . (int)$id);
            exit;
        }

        $periodId = (int)$id;
        $db = DB::get();

        $stmt = $db->prepare("
            SELECT *
            FROM payroll_periods
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$periodId, $this->companyId()]);
        $period = $stmt->fetch();

        if (!$period) {
            $_SESSION['flash_error'] = 'Výplata nebyla nalezena.';
            header('Location: /payrolls');
            exit;
        }

        $stmt = $db->prepare("
            UPDATE payroll_periods
            SET status = 'draft',
                approved_by = NULL,
                approved_at = NULL
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$periodId, $this->companyId()]);

        $_SESSION['flash_success'] = 'Výplata byla znovu otevřena.';
        header('Location: /payrolls/' . $periodId);
        exit;
    }
}
