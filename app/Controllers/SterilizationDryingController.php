<?php
namespace Controllers;

use Core\Auth;
use Core\CSRF;
use Core\DB;
use Core\Feature;

class SterilizationDryingController
{
    private function redirect(string $to): void
    {
        header("Location: {$to}");
        exit;
    }

    private function requireAccess(): void
    {
        if (!Auth::check()) {
            $this->redirect('/');
        }

        if (!Feature::enabled('sterilization_drying')) {
            $_SESSION['flash_error'] = "Modul Sterilizace a sušení není zapnutý.";
            $this->redirect('/dashboard');
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

    private function ensurePlaceBelongsToCompany(int $placeId): array
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT *
            FROM sterilization_places
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$placeId, $this->companyId()]);
        $place = $stmt->fetch();

        if (!$place) {
            $_SESSION['flash_error'] = "Vybrané místo nebylo nalezeno.";
            $this->redirect('/sterilization-drying');
        }

        return (array)$place;
    }

    private function ensureRecordBelongsToCompany(int $id): array
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT *
            FROM sterilization_records
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$id, $this->companyId()]);
        $row = $stmt->fetch();

        if (!$row) {
            $_SESSION['flash_error'] = "Záznam nebyl nalezen.";
            $this->redirect('/sterilization-drying');
        }

        return (array)$row;
    }

    private function normalizeType(string $type): string
    {
        $type = trim($type);
        return in_array($type, ['sterilization', 'drying'], true) ? $type : '';
    }

    private function validateInput(array $post): array
    {
        $placeId = (int)($post['place_id'] ?? 0);
        $recordDate = trim((string)($post['record_date'] ?? ''));
        $recordType = $this->normalizeType((string)($post['record_type'] ?? ''));
        $itemName = trim((string)($post['item_name'] ?? ''));
        $quantity = (int)($post['quantity'] ?? 0);
        $durationMinutes = (int)($post['duration_minutes'] ?? 0);

        $temperatureRaw = str_replace(',', '.', trim((string)($post['temperature_c'] ?? '')));
        $temperatureC = is_numeric($temperatureRaw) ? (float)$temperatureRaw : null;

        if ($placeId <= 0) {
            $_SESSION['flash_error'] = "Vyber místo.";
            $this->redirect('/sterilization-drying');
        }

        if ($recordDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $recordDate)) {
            $_SESSION['flash_error'] = "Vyplň platné datum.";
            $this->redirect('/sterilization-drying');
        }

        if (!\DateTimeImmutable::createFromFormat('Y-m-d', $recordDate)) {
            $_SESSION['flash_error'] = "Vyplň platné datum.";
            $this->redirect('/sterilization-drying');
        }

        if ($recordType === '') {
            $_SESSION['flash_error'] = "Vyber druh záznamu.";
            $this->redirect('/sterilization-drying');
        }

        if ($itemName === '') {
            $_SESSION['flash_error'] = "Vyplň předmět sterilizace / sušení.";
            $this->redirect('/sterilization-drying');
        }

        if ($quantity < 0) {
            $_SESSION['flash_error'] = "Množství nesmí být záporné.";
            $this->redirect('/sterilization-drying');
        }

        if ($durationMinutes < 0) {
            $_SESSION['flash_error'] = "Doba nesmí být záporná.";
            $this->redirect('/sterilization-drying');
        }

        if ($temperatureC === null) {
            $_SESSION['flash_error'] = "Teplota musí být číslo.";
            $this->redirect('/sterilization-drying');
        }

        return [
            'place_id' => $placeId,
            'record_date' => $recordDate,
            'record_type' => $recordType,
            'item_name' => $itemName,
            'quantity' => $quantity,
            'duration_minutes' => $durationMinutes,
            'temperature_c' => $temperatureC,
        ];
    }

    private function validateTypeAllowedForPlace(array $place, string $recordType): void
    {
        $isSterilizer = (int)($place['is_sterilizer'] ?? 0) === 1;
        $isDrying = (int)($place['is_drying'] ?? 0) === 1;

        if ($recordType === 'sterilization' && !$isSterilizer) {
            $_SESSION['flash_error'] = "Na tomto místě není povolen sterilizátor.";
            $this->redirect('/sterilization-drying');
        }

        if ($recordType === 'drying' && !$isDrying) {
            $_SESSION['flash_error'] = "Na tomto místě není povolena sušárna.";
            $this->redirect('/sterilization-drying');
        }
    }

    public function index(): void
    {
        $this->requireAccess();

        $db = DB::get();

        $stmt = $db->prepare("
            SELECT *
            FROM sterilization_places
            WHERE company_id = ?
            ORDER BY id ASC
        ");
        $stmt->execute([$this->companyId()]);
        $places = $stmt->fetchAll();

        $stmt = $db->prepare("
            SELECT r.*, p.name AS place_name,
                   u.first_name, u.last_name
            FROM sterilization_records r
            JOIN sterilization_places p ON p.id = r.place_id
            JOIN users u ON u.id = r.created_by
            WHERE r.company_id = ?
            ORDER BY p.name ASC, r.record_date DESC, r.id DESC
        ");
        $stmt->execute([$this->companyId()]);
        $rows = $stmt->fetchAll();

        $recordsByPlace = [];
        foreach ($rows as $row) {
            $pid = (int)$row['place_id'];
            if (!isset($recordsByPlace[$pid])) {
                $recordsByPlace[$pid] = [];
            }
            $recordsByPlace[$pid][] = $row;
        }

        $view = 'sterilization_drying/index';
        $title = 'Sterilizace a sušení';
        require __DIR__ . '/../Views/layout.php';
    }

    public function create(): void
    {
        $this->requireAccess();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            $this->redirect('/sterilization-drying');
        }

        $data = $this->validateInput($_POST);
        $place = $this->ensurePlaceBelongsToCompany($data['place_id']);
        $this->validateTypeAllowedForPlace($place, $data['record_type']);

        $db = DB::get();
        $stmt = $db->prepare("
            INSERT INTO sterilization_records
            (
                company_id, place_id, record_date, record_type,
                item_name, quantity, duration_minutes, temperature_c, created_by
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $this->companyId(),
            $data['place_id'],
            $data['record_date'],
            $data['record_type'],
            $data['item_name'],
            $data['quantity'],
            $data['duration_minutes'],
            $data['temperature_c'],
            $this->userId(),
        ]);

        $_SESSION['flash_success'] = "Záznam byl uložen.";
        $this->redirect('/sterilization-drying');
    }

    public function update($id): void
    {
        $this->requireAccess();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            $this->redirect('/sterilization-drying');
        }

        $id = (int)$id;
        $this->ensureRecordBelongsToCompany($id);

        $data = $this->validateInput($_POST);
        $place = $this->ensurePlaceBelongsToCompany($data['place_id']);
        $this->validateTypeAllowedForPlace($place, $data['record_type']);

        $db = DB::get();
        $stmt = $db->prepare("
            UPDATE sterilization_records
            SET place_id = ?,
                record_date = ?,
                record_type = ?,
                item_name = ?,
                quantity = ?,
                duration_minutes = ?,
                temperature_c = ?
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([
            $data['place_id'],
            $data['record_date'],
            $data['record_type'],
            $data['item_name'],
            $data['quantity'],
            $data['duration_minutes'],
            $data['temperature_c'],
            $id,
            $this->companyId(),
        ]);

        $_SESSION['flash_success'] = "Záznam byl upraven.";
        $this->redirect('/sterilization-drying');
    }

    public function delete($id): void
    {
        $this->requireAccess();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            $this->redirect('/sterilization-drying');
        }

        $id = (int)$id;
        $this->ensureRecordBelongsToCompany($id);

        $db = DB::get();
        $stmt = $db->prepare("
            DELETE FROM sterilization_records
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$id, $this->companyId()]);

        $_SESSION['flash_success'] = "Záznam byl smazán.";
        $this->redirect('/sterilization-drying');
    }
}