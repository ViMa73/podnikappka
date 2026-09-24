<?php
namespace Controllers;

use Core\Auth;
use Core\DB;
use Core\CSRF;

class TasksController
{
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

    private function isAdmin(): bool
    {
        return in_array(Auth::role(), ['owner', 'manager'], true);
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

    private function normalizeRepeatType(string $value): string
    {
        $value = trim($value);
        return in_array($value, ['none', 'daily', 'weekly', 'monthly', 'quarterly', 'yearly'], true) ? $value : 'none';
    }

    private function normalizeGroupColor(string $value): string
    {
        $value = trim(strtolower($value));

        $allowed = [
            'white',
            'yellow',
            'orange',
            'red',
            'purple',
            'blue',
            'cyan',
            'green',
            'gray',
        ];

        return in_array($value, $allowed, true) ? $value : 'white';
    }

    private function getUsers(): array
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT id, first_name, last_name
            FROM users
            WHERE company_id = ? AND status = 'active'
            ORDER BY last_name ASC, first_name ASC
        ");
        $stmt->execute([$this->companyId()]);
        return $stmt->fetchAll() ?: [];
    }

    private function getTaskById(int $id): ?array
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT *
            FROM tasks
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$id, $this->companyId()]);
        $row = $stmt->fetch();
        return $row ? (array)$row : null;
    }

    private function getGroupItemById(int $id): ?array
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT *
            FROM task_group_items
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$id, $this->companyId()]);
        $row = $stmt->fetch();
        return $row ? (array)$row : null;
    }

    private function getGroupById(int $id): ?array
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT *
            FROM task_groups
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$id, $this->companyId()]);
        $row = $stmt->fetch();
        return $row ? (array)$row : null;
    }

    public function index(): void
    {
        $this->requireAccess();

        $users = $this->isAdmin() ? $this->getUsers() : [];

        $view = 'tasks/index';
        $title = 'Úkoly';
        require __DIR__ . '/../Views/layout.php';
    }

    public function data(): void
    {
        $this->requireAccess();

        $db = DB::get();

        $stmt = $db->prepare("
            SELECT t.*,
                   u.first_name,
                   u.last_name,
                   cb.first_name AS completed_by_first_name,
                   cb.last_name AS completed_by_last_name
            FROM tasks t
            JOIN users u ON u.id = t.assigned_user_id
            LEFT JOIN users cb ON cb.id = t.completed_by
            WHERE t.company_id = ?
              AND t.assigned_user_id = ?
            ORDER BY
              CASE WHEN t.completed_at IS NULL THEN 0 ELSE 1 END,
              t.due_date IS NULL,
              t.due_date ASC,
              t.id DESC
        ");
        $stmt->execute([$this->companyId(), $this->userId()]);
        $personal = $stmt->fetchAll() ?: [];

        $stmt = $db->prepare("
            SELECT *
            FROM task_groups
            WHERE company_id = ?
            ORDER BY id ASC
        ");
        $stmt->execute([$this->companyId()]);
        $groups = $stmt->fetchAll() ?: [];

        $stmt = $db->prepare("
            SELECT i.*,
                   cb.first_name AS completed_by_first_name,
                   cb.last_name AS completed_by_last_name
            FROM task_group_items i
            LEFT JOIN users cb ON cb.id = i.completed_by
            WHERE i.company_id = ?
            ORDER BY
              i.group_id ASC,
              CASE WHEN i.completed_at IS NULL THEN 0 ELSE 1 END,
              i.sort_order ASC,
              i.id ASC
        ");
        $stmt->execute([$this->companyId()]);
        $rawItems = $stmt->fetchAll() ?: [];

        $items = [];
        foreach ($rawItems as $item) {
            $gid = (int) $item['group_id'];
            if (!isset($items[$gid])) {
                $items[$gid] = [];
            }
            $items[$gid][] = $item;
        }

        $all = [];
        if ($this->isAdmin()) {
            $stmt = $db->prepare("
                SELECT t.*,
                       u.first_name,
                       u.last_name,
                       cb.first_name AS completed_by_first_name,
                       cb.last_name AS completed_by_last_name
                FROM tasks t
                JOIN users u ON u.id = t.assigned_user_id
                LEFT JOIN users cb ON cb.id = t.completed_by
                WHERE t.company_id = ?
                ORDER BY
                  CASE WHEN t.completed_at IS NULL THEN 0 ELSE 1 END,
                  t.due_date IS NULL,
                  t.due_date ASC,
                  t.id DESC
            ");
            $stmt->execute([$this->companyId()]);
            $all = $stmt->fetchAll() ?: [];
        }

        $this->jsonResponse([
            'ok' => true,
            'personal' => $personal,
            'groups' => $groups,
            'items' => $items,
            'all' => $all,
        ]);
    }

    public function create(): void
    {
        $this->requireAccess();
        $data = $this->jsonInput();
        $this->requireJsonCsrf($data);

        $title = trim((string)($data['title'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $dueDate = trim((string)($data['due_date'] ?? ''));
        $repeatType = $this->normalizeRepeatType((string)($data['repeat_type'] ?? 'none'));
        $repeatInterval = max(1, (int)($data['repeat_interval'] ?? 1));
        $assignedUserId = (int)($data['assigned_user_id'] ?? 0);

        if ($title === '') {
            $this->jsonResponse(['ok' => false, 'message' => 'Vyplň název úkolu.'], 422);
        }

        if ($assignedUserId <= 0) {
            $assignedUserId = $this->userId();
        }

        if ($assignedUserId !== $this->userId() && !$this->isAdmin()) {
            $this->jsonResponse(['ok' => false, 'message' => 'Nemáš oprávnění vytvářet úkol jinému uživateli.'], 403);
        }

        if ($dueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            $this->jsonResponse(['ok' => false, 'message' => 'Neplatné datum splnění.'], 422);
        }

        $db = DB::get();
        $stmt = $db->prepare("
            INSERT INTO tasks (
                company_id, assigned_user_id, created_by, title, description, due_date, repeat_type, repeat_interval
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $this->companyId(),
            $assignedUserId,
            $this->userId(),
            $title,
            $description !== '' ? $description : null,
            $dueDate !== '' ? $dueDate : null,
            $repeatType,
            $repeatInterval
        ]);

        $this->jsonResponse(['ok' => true]);
    }

    public function delete(): void
    {
        $this->requireAccess();
        $data = $this->jsonInput();
        $this->requireJsonCsrf($data);

        if (!$this->isAdmin()) {
            $this->jsonResponse(['ok' => false, 'message' => 'Nemáš oprávnění smazat úkol.'], 403);
        }

        $id = (int)($data['id'] ?? 0);
        $task = $this->getTaskById($id);

        if (!$task) {
            $this->jsonResponse(['ok' => false, 'message' => 'Úkol nebyl nalezen.'], 404);
        }

        $db = DB::get();
        $stmt = $db->prepare("
            DELETE FROM tasks
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$id, $this->companyId()]);

        $this->jsonResponse(['ok' => true]);
    }

    public function update(): void
    {
        $this->requireAccess();
        $data = $this->jsonInput();
        $this->requireJsonCsrf($data);

        $id = (int)($data['id'] ?? 0);
        $task = $this->getTaskById($id);

        if (!$task) {
            $this->jsonResponse(['ok' => false, 'message' => 'Úkol nebyl nalezen.'], 404);
        }

        if ((int)$task['assigned_user_id'] !== $this->userId() && !$this->isAdmin()) {
            $this->jsonResponse(['ok' => false, 'message' => 'Nemáš oprávnění upravit tento úkol.'], 403);
        }

        $title = trim((string)($data['title'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $dueDate = trim((string)($data['due_date'] ?? ''));
        $repeatType = $this->normalizeRepeatType((string)($data['repeat_type'] ?? 'none'));
        $repeatInterval = max(1, (int)($data['repeat_interval'] ?? 1));
        $assignedUserId = (int)($data['assigned_user_id'] ?? 0);

        if ($title === '') {
            $this->jsonResponse(['ok' => false, 'message' => 'Vyplň název úkolu.'], 422);
        }

        if ($assignedUserId <= 0) {
            $assignedUserId = (int)$task['assigned_user_id'];
        }

        if ($assignedUserId !== $this->userId() && !$this->isAdmin()) {
            $this->jsonResponse(['ok' => false, 'message' => 'Nemáš oprávnění přiřadit úkol jinému uživateli.'], 403);
        }

        if ($dueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            $this->jsonResponse(['ok' => false, 'message' => 'Neplatné datum splnění.'], 422);
        }

        $db = DB::get();
        $stmt = $db->prepare("
            UPDATE tasks
            SET assigned_user_id = ?, title = ?, description = ?, due_date = ?, repeat_type = ?, repeat_interval = ?
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([
            $assignedUserId,
            $title,
            $description !== '' ? $description : null,
            $dueDate !== '' ? $dueDate : null,
            $repeatType,
            $repeatInterval,
            $id,
            $this->companyId()
        ]);

        $this->jsonResponse(['ok' => true]);
    }

    public function toggle(): void
    {
        $this->requireAccess();
        $data = $this->jsonInput();
        $this->requireJsonCsrf($data);

        $id = (int)($data['id'] ?? 0);
        $task = $this->getTaskById($id);

        if (!$task) {
            $this->jsonResponse(['ok' => false, 'message' => 'Úkol nebyl nalezen.'], 404);
        }

        if ((int)$task['assigned_user_id'] !== $this->userId() && !$this->isAdmin()) {
            $this->jsonResponse(['ok' => false, 'message' => 'Nemáš oprávnění změnit stav tohoto úkolu.'], 403);
        }

        $db = DB::get();

        if (!empty($task['completed_at'])) {
            $stmt = $db->prepare("
                UPDATE tasks
                SET completed_at = NULL, completed_by = NULL
                WHERE id = ? AND company_id = ?
            ");
            $stmt->execute([$id, $this->companyId()]);
            $this->jsonResponse(['ok' => true]);
        }

        $stmt = $db->prepare("
            UPDATE tasks
            SET completed_at = NOW(), completed_by = ?
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$this->userId(), $id, $this->companyId()]);

        if (($task['repeat_type'] ?? 'none') !== 'none') {
            $dueDate = $task['due_date'] ? new \DateTimeImmutable($task['due_date']) : new \DateTimeImmutable('today');
            $interval = max(1, (int)($task['repeat_interval'] ?? 1));
            $repeatType = (string)$task['repeat_type'];

            $nextDue = null;
            if ($repeatType === 'daily') {
                $nextDue = $dueDate->modify("+{$interval} day");
            }
            if ($repeatType === 'weekly') {
                $nextDue = $dueDate->modify("+{$interval} week");
            }
            if ($repeatType === 'monthly') {
                $nextDue = $dueDate->modify("+{$interval} month");
            }
            if ($repeatType === 'quarterly') {
                $months = $interval * 3;
                $nextDue = $dueDate->modify("+{$months} month");
            }
            if ($repeatType === 'yearly') {
                $nextDue = $dueDate->modify("+{$interval} year");
            }

            if ($nextDue) {
                $stmt = $db->prepare("
                    INSERT INTO tasks (
                        company_id, assigned_user_id, created_by, title, description, due_date, repeat_type, repeat_interval
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $this->companyId(),
                    (int)$task['assigned_user_id'],
                    $this->userId(),
                    (string)$task['title'],
                    $task['description'] ?: null,
                    $nextDue->format('Y-m-d'),
                    $repeatType,
                    $interval
                ]);
            }
        }

        $this->jsonResponse(['ok' => true]);
    }

    public function createGroup(): void
    {
        $this->requireAccess();
        $data = $this->jsonInput();
        $this->requireJsonCsrf($data);

        $title = trim((string)($data['title'] ?? ''));
        $color = $this->normalizeGroupColor((string)($data['color'] ?? 'yellow'));

        if ($title === '') {
            $this->jsonResponse(['ok' => false, 'message' => 'Vyplň název skupiny.'], 422);
        }

        $db = DB::get();
        $stmt = $db->prepare("
            INSERT INTO task_groups (company_id, title, color, created_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $this->companyId(),
            $title,
            $color,
            $this->userId()
        ]);

        $this->jsonResponse(['ok' => true]);
    }

    public function updateGroup(): void
    {
        $this->requireAccess();
        $data = $this->jsonInput();
        $this->requireJsonCsrf($data);

        $id = (int)($data['id'] ?? 0);
        $group = $this->getGroupById($id);

        if (!$group) {
            $this->jsonResponse(['ok' => false, 'message' => 'Skupina nebyla nalezena.'], 404);
        }

        $title = trim((string)($data['title'] ?? ''));
        $color = $this->normalizeGroupColor((string)($data['color'] ?? 'yellow'));

        if ($title === '') {
            $this->jsonResponse(['ok' => false, 'message' => 'Vyplň název skupiny.'], 422);
        }

        $db = DB::get();
        $stmt = $db->prepare("
            UPDATE task_groups
            SET title = ?, color = ?
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([
            $title,
            $color,
            $id,
            $this->companyId()
        ]);

        $this->jsonResponse(['ok' => true]);
    }

    public function deleteGroup(): void
    {
        $this->requireAccess();
        $data = $this->jsonInput();
        $this->requireJsonCsrf($data);

        $id = (int)($data['id'] ?? 0);
        $group = $this->getGroupById($id);

        if (!$group) {
            $this->jsonResponse(['ok' => false, 'message' => 'Skupina nebyla nalezena.'], 404);
        }

        $db = DB::get();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("
                DELETE FROM task_group_items
                WHERE group_id = ? AND company_id = ?
            ");
            $stmt->execute([$id, $this->companyId()]);

            $stmt = $db->prepare("
                DELETE FROM task_groups
                WHERE id = ? AND company_id = ?
            ");
            $stmt->execute([$id, $this->companyId()]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->jsonResponse(['ok' => false, 'message' => 'Skupinu se nepodařilo smazat.'], 500);
        }

        $this->jsonResponse(['ok' => true]);
    }

    public function createGroupItem(): void
    {
        $this->requireAccess();
        $data = $this->jsonInput();
        $this->requireJsonCsrf($data);

        $groupId = (int)($data['group_id'] ?? 0);
        $title = trim((string)($data['title'] ?? ''));

        if ($groupId <= 0 || $title === '') {
            $this->jsonResponse(['ok' => false, 'message' => 'Vyplň název úkolu.'], 422);
        }

        if (!$this->getGroupById($groupId)) {
            $this->jsonResponse(['ok' => false, 'message' => 'Skupina nebyla nalezena.'], 404);
        }

        $db = DB::get();
        $stmt = $db->prepare("
            SELECT COALESCE(MAX(sort_order), 0) + 1
            FROM task_group_items
            WHERE company_id = ? AND group_id = ?
        ");
        $stmt->execute([$this->companyId(), $groupId]);
        $sortOrder = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("
            INSERT INTO task_group_items (company_id, group_id, title, sort_order, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $this->companyId(),
            $groupId,
            $title,
            $sortOrder,
            $this->userId()
        ]);

        $this->jsonResponse(['ok' => true]);
    }

    public function toggleGroupItem(): void
    {
        $this->requireAccess();
        $data = $this->jsonInput();
        $this->requireJsonCsrf($data);

        $id = (int)($data['id'] ?? 0);
        $item = $this->getGroupItemById($id);

        if (!$item) {
            $this->jsonResponse(['ok' => false, 'message' => 'Skupinový úkol nebyl nalezen.'], 404);
        }

        $db = DB::get();

        if (!empty($item['completed_at'])) {
            $stmt = $db->prepare("
                UPDATE task_group_items
                SET completed_at = NULL, completed_by = NULL
                WHERE id = ? AND company_id = ?
            ");
            $stmt->execute([$id, $this->companyId()]);
            $this->jsonResponse(['ok' => true]);
        }

        $stmt = $db->prepare("
            UPDATE task_group_items
            SET completed_at = NOW(), completed_by = ?
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$this->userId(), $id, $this->companyId()]);

        $this->jsonResponse(['ok' => true]);
    }

    public function deleteCompletedGroupItems(): void
    {
        $this->requireAccess();
        $data = $this->jsonInput();
        $this->requireJsonCsrf($data);

        $groupId = (int)($data['group_id'] ?? 0);
        $group = $this->getGroupById($groupId);

        if (!$group) {
            $this->jsonResponse(['ok' => false, 'message' => 'Skupina nebyla nalezena.'], 404);
        }

        $db = DB::get();
        $stmt = $db->prepare("
            DELETE FROM task_group_items
            WHERE company_id = ?
              AND group_id = ?
              AND completed_at IS NOT NULL
        ");
        $stmt->execute([
            $this->companyId(),
            $groupId
        ]);

        $this->jsonResponse(['ok' => true]);
    }

    public function dashboardData(): void
    {
        $this->requireAccess();

        if (!\Core\Feature::enabled('tasks')) {
            $this->jsonResponse(['ok' => false, 'message' => 'Modul Úkoly není zapnutý.'], 404);
        }

        $db = DB::get();

        $stmt = $db->prepare("
            SELECT t.*,
                cb.first_name AS completed_by_first_name,
                cb.last_name AS completed_by_last_name
            FROM tasks t
            LEFT JOIN users cb ON cb.id = t.completed_by
            WHERE t.company_id = ?
            AND t.assigned_user_id = ?
            AND t.completed_at IS NULL
            ORDER BY
            t.due_date IS NULL,
            t.due_date ASC,
            t.id DESC
        ");
        $stmt->execute([$this->companyId(), $this->userId()]);
        $tasks = $stmt->fetchAll() ?: [];

        $this->jsonResponse([
            'ok' => true,
            'tasks' => $tasks,
        ]);
    }

    public function dashboardGroupsData(): void
    {
        $this->requireAccess();

        if (!\Core\Feature::enabled('tasks')) {
            $this->jsonResponse(['ok' => false, 'message' => 'Modul Úkoly není zapnutý.'], 404);
        }

        $db = DB::get();

        $stmt = $db->prepare("
            SELECT *
            FROM task_groups
            WHERE company_id = ?
            ORDER BY id ASC
            LIMIT 3
        ");
        $stmt->execute([$this->companyId()]);
        $groups = $stmt->fetchAll() ?: [];

        $groupIds = array_map(static fn($g) => (int)$g['id'], $groups);

        $items = [];
        if (!empty($groupIds)) {
            $placeholders = implode(',', array_fill(0, count($groupIds), '?'));

            $params = array_merge([$this->companyId()], $groupIds);

            $stmt = $db->prepare("
                SELECT i.*,
                       cb.first_name AS completed_by_first_name,
                       cb.last_name AS completed_by_last_name
                FROM task_group_items i
                LEFT JOIN users cb ON cb.id = i.completed_by
                WHERE i.company_id = ?
                  AND i.group_id IN ($placeholders)
                ORDER BY
                  i.group_id ASC,
                  CASE WHEN i.completed_at IS NULL THEN 0 ELSE 1 END,
                  i.sort_order ASC,
                  i.id ASC
            ");
            $stmt->execute($params);
            $rawItems = $stmt->fetchAll() ?: [];

            foreach ($rawItems as $item) {
                $gid = (int)$item['group_id'];
                if (!isset($items[$gid])) {
                    $items[$gid] = [];
                }
                $items[$gid][] = $item;
            }
        }

        $this->jsonResponse([
            'ok' => true,
            'groups' => $groups,
            'items' => $items,
        ]);
    }
}
