<?php
namespace Controllers;

use Core\Auth;
use Core\DB;
use Core\CSRF;
use Core\Feature;
use Core\WeekDays;

class ServicePlacesController
{
    private function forbidJson()
    {
      http_response_code(403);
      echo json_encode(['error' => 'forbidden']);
      exit;
    }

    private function guard()
    {
        if (!\Core\Feature::enabled('services') || !\Core\Auth::canEditSettings()) {
            $this->forbidJson();
        }
    }

    public function create()
    {
        $this->guard();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář.';
            header('Location: /settings');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $notesEnabled = isset($_POST['notes_enabled']) ? 1 : 0;
        $lockOnHoliday = isset($_POST['lock_on_holiday']) ? 1 : 0;

        if ($name === '') {
            $_SESSION['flash_error'] = 'Název místa je povinný.';
            header('Location: /settings');
            exit;
        }

        $mask = WeekDays::toMask($_POST['days'] ?? []);

        $stmt = DB::get()->prepare("
            INSERT INTO service_places (company_id, name, description, days_mask, notes_enabled, lock_on_holiday)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            Auth::companyId(),
            $name,
            $description ?: null,
            $mask,
            $notesEnabled,
            $lockOnHoliday
        ]);

        $_SESSION['flash_success'] = 'Místo bylo přidáno.';
        header('Location: /settings');
        exit;
    }

    public function delete($id)
    {
        $this->guard();

        $stmt = DB::get()->prepare("
            DELETE FROM service_places
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$id, Auth::companyId()]);

        $_SESSION['flash_success'] = 'Místo bylo odstraněno.';
        header('Location: /settings');
        exit;
    }
    public function update($id)
    {
        $this->guard();

        if (!\Core\CSRF::check($_POST['_csrf'] ?? '')) {
            http_response_code(400);
            echo json_encode(['error' => 'csrf']);
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Název je povinný']);
            exit;
        }

        $notesEnabled = isset($_POST['notes_enabled']) ? 1 : 0;
        $lockOnHoliday = isset($_POST['lock_on_holiday']) ? 1 : 0;

        $mask = \Core\WeekDays::toMask($_POST['days'] ?? []);

        $stmt = \Core\DB::get()->prepare("
            UPDATE service_places
            SET name = ?, description = ?, days_mask = ?, notes_enabled = ?, lock_on_holiday = ?
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([
            $name,
            trim($_POST['description'] ?? '') ?: null,
            $mask,
            $notesEnabled,
            $lockOnHoliday,
            $id,
            \Core\Auth::companyId()
        ]);

        echo json_encode(['ok' => true]);
        exit;
    }
}
