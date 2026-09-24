<?php
namespace Controllers;

use Core\Auth;
use Core\CSRF;
use Core\DB;

class VacationsController
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
        if (!\Core\Feature::enabled('vacations')) {
            $_SESSION['flash_error'] = "Modul Dovolené není zapnutý.";
            $this->redirect('/dashboard');
        }
    }

    private function requireManagePermission(): void
    {
        if (!in_array(Auth::role(), ['owner', 'manager'], true)) {
            $_SESSION['flash_error'] = "Nemáš oprávnění zobrazit všechny dovolené.";
            $this->redirect('/vacations');
        }
    }

    private function findVacationForCompany(int $id): ?array
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT v.*, u.first_name, u.last_name
            FROM vacations v
            JOIN users u ON u.id = v.user_id
            WHERE v.id = ? AND v.company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$id, Auth::companyId()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function canEditVacation(array $vacation): bool
    {
        $me = (int)($_SESSION['user_id'] ?? 0);
        $ownerId = (int)$vacation['user_id'];

        if ($me === $ownerId) return true;
        if (Auth::role() === 'owner') return true;
        if (Auth::role() === 'manager') return true;

        return false;
    }

    public function index(): void
    {
        $this->requireAuth();
        $this->requireFeature();

        $db = DB::get();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $perPage = 15;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');

        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM vacations
            WHERE company_id = ? AND user_id = ?
        ");
        $stmt->execute([Auth::companyId(), $userId]);
        $total = (int)$stmt->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $perPage));

        $stmt = $db->prepare("
            SELECT *
            FROM vacations
            WHERE company_id = ? AND user_id = ?
            ORDER BY
                CASE WHEN date_to >= ? THEN 0 ELSE 1 END ASC,
                date_from DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute([Auth::companyId(), $userId, $today]);
        $vacations = $stmt->fetchAll();

        $view = 'vacations/index';
        $title = 'Dovolené';
        require __DIR__ . '/../Views/layout.php';
    }

    public function all(): void
    {
        $this->requireAuth();
        $this->requireFeature();
        $this->requireManagePermission();

        $db = DB::get();

        $perPage = 15;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');

        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM vacations
            WHERE company_id = ?
        ");
        $stmt->execute([Auth::companyId()]);
        $total = (int)$stmt->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $perPage));

        $stmt = $db->prepare("
            SELECT v.*, u.first_name, u.last_name
            FROM vacations v
            JOIN users u ON u.id = v.user_id
            WHERE v.company_id = ?
            ORDER BY
                CASE WHEN v.date_to >= ? THEN 0 ELSE 1 END ASC,
                v.date_from DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute([Auth::companyId(), $today]);
        $vacations = $stmt->fetchAll();

        $view = 'vacations/all';
        $title = 'Všechny dovolené';
        require __DIR__ . '/../Views/layout.php';
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->requireFeature();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            $this->redirect('/vacations');
        }

        $title = trim($_POST['title'] ?? '');
        $dateFrom = trim($_POST['date_from'] ?? '');
        $dateTo = trim($_POST['date_to'] ?? '');
        $totalHours = trim($_POST['total_hours'] ?? '');
        $note = trim($_POST['note'] ?? '');

        if ($title === '' || $dateFrom === '' || $dateTo === '') {
            $_SESSION['flash_error'] = "Vyplň název, počáteční a koncové datum.";
            $this->redirect('/vacations');
        }

        $from = \DateTimeImmutable::createFromFormat('Y-m-d', $dateFrom);
        $to   = \DateTimeImmutable::createFromFormat('Y-m-d', $dateTo);

        if (!$from || !$to) {
            $_SESSION['flash_error'] = "Datum není platné.";
            $this->redirect('/vacations');
        }

        if ($to < $from) {
            $_SESSION['flash_error'] = "Koncové datum nesmí být dříve než počáteční.";
            $this->redirect('/vacations');
        }

        $hours = null;
        if ($totalHours !== '') {
            $totalHours = str_replace(',', '.', $totalHours);
            if (!is_numeric($totalHours)) {
                $_SESSION['flash_error'] = "Celkem hodin musí být číslo.";
                $this->redirect('/vacations');
            }
            $hours = (float)$totalHours;
        }

        $db = DB::get();
        $stmt = $db->prepare("
            INSERT INTO vacations (company_id, user_id, title, date_from, date_to, total_hours, note)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            Auth::companyId(),
            (int)($_SESSION['user_id'] ?? 0),
            $title,
            $dateFrom,
            $dateTo,
            $hours,
            $note !== '' ? $note : null
        ]);

        $_SESSION['flash_success'] = "Dovolená byla vytvořena.";
        $this->redirect('/vacations');
    }

    public function show($id): void
    {
        $this->requireAuth();
        $this->requireFeature();

        $vacation = $this->findVacationForCompany((int)$id);

        if (!$vacation) {
            http_response_code(404);
            $view = 'errors/404';
            $title = 'Nenalezeno';
            require __DIR__ . '/../Views/layout.php';
            exit;
        }

        if (!$this->canEditVacation($vacation)) {
            $_SESSION['flash_error'] = "Nemáš oprávnění zobrazit tuto dovolenou.";
            $this->redirect('/vacations');
        }

        $canEdit = true;

        $view = 'vacations/show';
        $title = 'Detail dovolené';
        require __DIR__ . '/../Views/layout.php';
    }

    public function update($id): void
    {
        $this->requireAuth();
        $this->requireFeature();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            $this->redirect('/vacations/' . (int)$id);
        }

        $vacation = $this->findVacationForCompany((int)$id);
        if (!$vacation) {
            $_SESSION['flash_error'] = "Dovolená nebyla nalezena.";
            $this->redirect('/vacations');
        }

        if (!$this->canEditVacation($vacation)) {
            $_SESSION['flash_error'] = "Nemáš oprávnění upravit tuto dovolenou.";
            $this->redirect('/vacations');
        }

        $title = trim($_POST['title'] ?? '');
        $dateFrom = trim($_POST['date_from'] ?? '');
        $dateTo = trim($_POST['date_to'] ?? '');
        $totalHours = trim($_POST['total_hours'] ?? '');
        $note = trim($_POST['note'] ?? '');

        if ($title === '' || $dateFrom === '' || $dateTo === '') {
            $_SESSION['flash_error'] = "Vyplň název, počáteční a koncové datum.";
            $this->redirect('/vacations/' . (int)$id);
        }

        $from = \DateTimeImmutable::createFromFormat('Y-m-d', $dateFrom);
        $to   = \DateTimeImmutable::createFromFormat('Y-m-d', $dateTo);

        if (!$from || !$to) {
            $_SESSION['flash_error'] = "Datum není platné.";
            $this->redirect('/vacations/' . (int)$id);
        }

        if ($to < $from) {
            $_SESSION['flash_error'] = "Koncové datum nesmí být dříve než počáteční.";
            $this->redirect('/vacations/' . (int)$id);
        }

        $hours = null;
        if ($totalHours !== '') {
            $totalHours = str_replace(',', '.', $totalHours);
            if (!is_numeric($totalHours)) {
                $_SESSION['flash_error'] = "Celkem hodin musí být číslo.";
                $this->redirect('/vacations/' . (int)$id);
            }
            $hours = (float)$totalHours;
        }

        $db = DB::get();
        $stmt = $db->prepare("
            UPDATE vacations
            SET title = ?, date_from = ?, date_to = ?, total_hours = ?, note = ?
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([
            $title,
            $dateFrom,
            $dateTo,
            $hours,
            $note !== '' ? $note : null,
            (int)$id,
            Auth::companyId()
        ]);

        $_SESSION['flash_success'] = "Dovolená byla uložena.";
        $this->redirect('/vacations/' . (int)$id);
    }

    public function delete($id): void
    {
        $this->requireAuth();
        $this->requireFeature();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            $this->redirect('/vacations/' . (int)$id);
        }

        $vacation = $this->findVacationForCompany((int)$id);
        if (!$vacation) {
            $_SESSION['flash_error'] = "Dovolená nebyla nalezena.";
            $this->redirect('/vacations');
        }

        if (!$this->canEditVacation($vacation)) {
            $_SESSION['flash_error'] = "Nemáš oprávnění smazat tuto dovolenou.";
            $this->redirect('/vacations');
        }

        $db = DB::get();
        $stmt = $db->prepare("
            DELETE FROM vacations
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([
            (int)$id,
            Auth::companyId()
        ]);

        $_SESSION['flash_success'] = "Dovolená byla smazána.";
        $this->redirect('/vacations');
    }
}
