<?php
namespace Controllers;

use Core\DB;
use Core\Auth;
use Core\CSRF;
use Core\WeekDays;
use Core\Feature;
use Core\CzechHolidays;

class ServicesController
{
    private function redirect(string $to)
    {
        header("Location: {$to}");
        exit;
    }

    private function forbid(string $msg = "Nemáš oprávnění.")
    {
        $_SESSION['flash_error'] = $msg;
        $this->redirect('/services');
    }

    private function requireEnabled()
    {
        if (!Feature::enabled('services')) {
            $_SESSION['flash_error'] = "Modul Služby není zapnutý.";
            $this->redirect('/dashboard');
        }
    }

    private function companySettings(): array
    {
        $db = DB::get();
        $stmt = $db->prepare("SELECT manager_can_assign_services FROM companies WHERE id = ? LIMIT 1");
        $stmt->execute([Auth::companyId()]);
        $row = $stmt->fetch();
        return $row ? (array)$row : ['manager_can_assign_services' => 0];
    }

    private function canManagerAssignColleagues(): bool
    {
        if (Auth::role() !== 'manager') return false;
        $c = $this->companySettings();
        return !empty($c['manager_can_assign_services']);
    }

    private function canAssignColleague(): bool
    {
        return Auth::role() === 'owner' || $this->canManagerAssignColleagues();
    }

    private function canManageColleagueNotes(): bool
    {
        return Auth::role() === 'owner' || $this->canManagerAssignColleagues();
    }

    private function canUnassign(int $assignedUserId): bool
    {
        if ((int)($_SESSION['user_id'] ?? 0) === $assignedUserId) return true;
        if (Auth::role() === 'owner') return true;
        if (Auth::role() === 'manager' && $this->canManagerAssignColleagues()) return true;
        return false;
    }

    private function ensurePlaceBelongsToCompany(int $placeId): array
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT id, name, description, days_mask, notes_enabled, lock_on_holiday
            FROM service_places
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$placeId, Auth::companyId()]);
        $place = $stmt->fetch();
        if (!$place) {
            $_SESSION['flash_error'] = "Neplatné místo.";
            $this->redirect('/services');
        }
        return (array)$place;
    }

    private function normalizeMonday(?string $weekParam): \DateTimeImmutable
    {
        try {
            $d = $weekParam ? new \DateTimeImmutable($weekParam) : new \DateTimeImmutable('now');
        } catch (\Throwable $e) {
            $d = new \DateTimeImmutable('now');
        }
        return $d->modify('monday this week');
    }

    public function index()
    {
        if (!Auth::check()) $this->redirect('/');
        $this->requireEnabled();

        $db = DB::get();

        $monday = $this->normalizeMonday(trim($_GET['week'] ?? ''));
        $sunday = $monday->modify('+6 days');

        // místa
        $stmt = $db->prepare("
            SELECT id, name, description, days_mask, notes_enabled, lock_on_holiday
            FROM service_places
            WHERE company_id = ?
            ORDER BY id ASC
        ");
        $stmt->execute([Auth::companyId()]);
        $places = $stmt->fetchAll();

        // určení posledního dne ve sloupcích
        $dayKeys = array_keys(WeekDays::$days);
        $maxOpenIdx = 4; // Po..Pá

        foreach ($places as $p) {
            $mask = (int)($p['days_mask'] ?? 0);

            for ($idx = count($dayKeys) - 1; $idx >= 0; $idx--) {
                $key = $dayKeys[$idx];
                $bit = (int)(WeekDays::$days[$key] ?? 0);
                if ($bit && (($mask & $bit) === $bit)) {
                    $maxOpenIdx = max($maxOpenIdx, $idx);
                    break;
                }
            }
        }

        // sloupce dnů
        $days = [];
        foreach ($dayKeys as $i => $key) {
            if ($i > $maxOpenIdx) break;

            $date = $monday->modify("+{$i} days");

            $days[] = [
                'idx'   => $i,
                'key'   => $key,
                'bit'   => (int)(WeekDays::$days[$key] ?? 0),
                'name'  => WeekDays::$names[$key] ?? $key,
                'date'  => $date,
                'dateY' => $date->format('Y-m-d'),
                'label' => (WeekDays::$names[$key] ?? $key) . ' ' . $date->format('j.n.'),
                'holiday_name' => CzechHolidays::getHolidayName($date)
            ];
        }

        // zápisy služeb pro týden
        $stmt = $db->prepare("
            SELECT a.place_id, a.service_date, a.user_id, a.note,
                   u.first_name, u.last_name
            FROM service_assignments a
            JOIN users u ON u.id = a.user_id
            WHERE a.company_id = ?
              AND a.service_date BETWEEN ? AND ?
        ");
        $stmt->execute([
            Auth::companyId(),
            $monday->format('Y-m-d'),
            $sunday->format('Y-m-d')
        ]);
        $rows = $stmt->fetchAll();

        $assignments = [];
        foreach ($rows as $r) {
            $pid = (int)($r['place_id'] ?? 0);
            $d   = (string)($r['service_date'] ?? '');
            if ($pid <= 0 || $d === '') continue;

            $assignments[$pid][$d] = [
                'user_id' => (int)($r['user_id'] ?? 0),
                'name'    => trim((string)($r['first_name'] ?? '') . ' ' . (string)($r['last_name'] ?? '')),
                'note'    => (string)($r['note'] ?? ''),
            ];
        }

        // kolegové pro "zapsat kolegu" a "poznámka kolegy"
        $colleagues = [];
        if ($this->canAssignColleague()) {
            $stmt = $db->prepare("
                SELECT id, first_name, last_name, role, status
                FROM users
                WHERE company_id = ? AND status = 'active'
                ORDER BY last_name ASC, first_name ASC
            ");
            $stmt->execute([Auth::companyId()]);
            $colleagues = $stmt->fetchAll();
        }

        // dovolené do řádku v tabulce
        $vacationsByDate = [];
        if (Feature::enabled('vacations')) {
            $stmt = $db->prepare("
                SELECT v.date_from, v.date_to, u.first_name, u.last_name
                FROM vacations v
                JOIN users u ON u.id = v.user_id
                WHERE v.company_id = ?
                  AND v.date_from <= ?
                  AND v.date_to >= ?
                ORDER BY u.last_name ASC, u.first_name ASC
            ");
            $stmt->execute([
                Auth::companyId(),
                $sunday->format('Y-m-d'),
                $monday->format('Y-m-d')
            ]);
            $vacationRows = $stmt->fetchAll();

            foreach ($vacationRows as $row) {
                $from = new \DateTimeImmutable($row['date_from']);
                $to   = new \DateTimeImmutable($row['date_to']);
                $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));

                $cursor = $from;
                while ($cursor <= $to) {
                    $dateKey = $cursor->format('Y-m-d');

                    if ($dateKey >= $monday->format('Y-m-d') && $dateKey <= $sunday->format('Y-m-d')) {
                        $vacationsByDate[$dateKey][] = $name;
                    }

                    $cursor = $cursor->modify('+1 day');
                }
            }
        }

        // obecné denní poznámky pro týden
        $dayNotesByDate = [];
        $stmt = $db->prepare("
            SELECT n.note_date, n.note, n.user_id, u.first_name, u.last_name
            FROM service_day_notes n
            JOIN users u ON u.id = n.user_id
            WHERE n.company_id = ?
              AND n.note_date BETWEEN ? AND ?
              AND n.note IS NOT NULL
              AND TRIM(n.note) <> ''
            ORDER BY n.note_date ASC, u.last_name ASC, u.first_name ASC
        ");
        $stmt->execute([
            Auth::companyId(),
            $monday->format('Y-m-d'),
            $sunday->format('Y-m-d')
        ]);
        $dayNoteRows = $stmt->fetchAll();

        foreach ($dayNoteRows as $row) {
            $dateKey = (string)$row['note_date'];
            $dayNotesByDate[$dateKey][] = [
                'user_id' => (int)$row['user_id'],
                'name'    => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                'note'    => (string)($row['note'] ?? ''),
            ];
        }

        // moje poznámky pro předvyplnění modalu
        $myDayNotes = [];
        $stmt = $db->prepare("
            SELECT note_date, note
            FROM service_day_notes
            WHERE company_id = ?
              AND user_id = ?
              AND note_date BETWEEN ? AND ?
        ");
        $stmt->execute([
            Auth::companyId(),
            (int)($_SESSION['user_id'] ?? 0),
            $monday->format('Y-m-d'),
            $sunday->format('Y-m-d')
        ]);
        foreach ($stmt->fetchAll() as $row) {
            $myDayNotes[(string)$row['note_date']] = (string)($row['note'] ?? '');
        }

        // poznámky kolegů pro owner/manager modal
        $colleagueDayNotes = [];
        if ($this->canManageColleagueNotes()) {
            $stmt = $db->prepare("
                SELECT note_date, user_id, note
                FROM service_day_notes
                WHERE company_id = ?
                  AND note_date BETWEEN ? AND ?
            ");
            $stmt->execute([
                Auth::companyId(),
                $monday->format('Y-m-d'),
                $sunday->format('Y-m-d')
            ]);
            foreach ($stmt->fetchAll() as $row) {
                $colleagueDayNotes[(string)$row['note_date']][(int)$row['user_id']] = (string)($row['note'] ?? '');
            }
        }

        $company = $this->companySettings();

        $view = 'services/index';
        $title = 'Služby';
        require __DIR__ . '/../Views/layout.php';
    }

    public function saveNote()
    {
        if (!Auth::check()) $this->redirect('/');
        $this->requireEnabled();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            $this->redirect('/services');
        }

        $placeId = (int)($_POST['place_id'] ?? 0);
        $dateStr = trim($_POST['service_date'] ?? '');
        $note    = trim($_POST['note'] ?? '');
        $week    = trim($_POST['_week'] ?? '');

        if ($placeId <= 0 || $dateStr === '') {
            $_SESSION['flash_error'] = "Neplatná data.";
            $this->redirect($week ? "/services?week=" . urlencode($week) : "/services");
        }

        $place = $this->ensurePlaceBelongsToCompany($placeId);

        if ((int)($place['notes_enabled'] ?? 0) !== 1) {
            $_SESSION['flash_error'] = "Poznámky nejsou pro toto místo povolené.";
            $this->redirect($week ? "/services?week=" . urlencode($week) : "/services");
        }

        $db = DB::get();

        $stmt = $db->prepare("
            SELECT user_id
            FROM service_assignments
            WHERE company_id = ? AND place_id = ? AND service_date = ?
            LIMIT 1
        ");
        $stmt->execute([Auth::companyId(), $placeId, $dateStr]);
        $row = $stmt->fetch();

        if (!$row) {
            $_SESSION['flash_error'] = "Nelze uložit poznámku – služba není obsazená.";
            $this->redirect($week ? "/services?week=" . urlencode($week) : "/services");
        }

        $assignedUserId = (int)$row['user_id'];

        if (!$this->canUnassign($assignedUserId)) {
            $_SESSION['flash_error'] = "Nemáš oprávnění upravit poznámku.";
            $this->redirect($week ? "/services?week=" . urlencode($week) : "/services");
        }

        $stmt = $db->prepare("
            UPDATE service_assignments
            SET note = ?
            WHERE company_id = ? AND place_id = ? AND service_date = ?
            LIMIT 1
        ");
        $stmt->execute([
            $note !== '' ? $note : null,
            Auth::companyId(),
            $placeId,
            $dateStr
        ]);

        $_SESSION['flash_success'] = "Poznámka byla uložena.";
        $this->redirect($week ? "/services?week=" . urlencode($week) : "/services");
    }

    public function saveDayNote()
    {
        if (!Auth::check()) $this->redirect('/');
        $this->requireEnabled();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            $this->redirect('/services');
        }

        $dateStr = trim($_POST['note_date'] ?? '');
        $note    = trim($_POST['note'] ?? '');
        $week    = trim($_POST['_week'] ?? '');
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);

        if ($dateStr === '') {
            $_SESSION['flash_error'] = "Chybí datum poznámky.";
            $this->redirect($week ? "/services?week=" . urlencode($week) : "/services");
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $dateStr);
        if (!$dt) {
            $_SESSION['flash_error'] = "Neplatné datum.";
            $this->redirect($week ? "/services?week=" . urlencode($week) : "/services");
        }

        // Když není user_id poslané, beru aktuálně přihlášeného uživatele
        if ($targetUserId <= 0) {
            $targetUserId = $currentUserId;
        }

        // Pokud uživatel upravuje cizí poznámku, musí na to mít právo
        if ($targetUserId !== $currentUserId) {
            if (!$this->canManageColleagueNotes()) {
                $_SESSION['flash_error'] = "Nemáš oprávnění upravovat poznámku kolegy.";
                $this->redirect($week ? "/services?week=" . urlencode($week) : "/services");
            }

            $db = DB::get();
            $stmt = $db->prepare("
                SELECT id
                FROM users
                WHERE id = ? AND company_id = ? AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$targetUserId, Auth::companyId()]);
            if (!$stmt->fetch()) {
                $_SESSION['flash_error'] = "Vybraný uživatel neexistuje.";
                $this->redirect($week ? "/services?week=" . urlencode($week) : "/services");
            }
        }

        $db = DB::get();

        if ($note === '') {
            $stmt = $db->prepare("
                DELETE FROM service_day_notes
                WHERE company_id = ? AND user_id = ? AND note_date = ?
                LIMIT 1
            ");
            $stmt->execute([
                Auth::companyId(),
                $targetUserId,
                $dateStr
            ]);

            $_SESSION['flash_success'] = "Poznámka dne byla smazána.";
            $this->redirect($week ? "/services?week=" . urlencode($week) : "/services");
        }

        $stmt = $db->prepare("
            INSERT INTO service_day_notes (company_id, user_id, note_date, note)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE note = VALUES(note)
        ");
        $stmt->execute([
            Auth::companyId(),
            $targetUserId,
            $dateStr,
            $note
        ]);

        $_SESSION['flash_success'] = "Poznámka dne byla uložena.";
        $this->redirect($week ? "/services?week=" . urlencode($week) : "/services");
    }

    public function assign()
    {
        if (!Auth::check()) $this->redirect('/');
        $this->requireEnabled();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            $this->redirect('/services');
        }

        $placeId = (int)($_POST['place_id'] ?? 0);
        $dateStr = trim($_POST['service_date'] ?? '');
        $targetUserId = (int)($_POST['user_id'] ?? 0);

        if ($placeId <= 0 || $dateStr === '') {
            $_SESSION['flash_error'] = "Neplatná data.";
            $this->redirect('/services');
        }

        if ($targetUserId <= 0) {
            $targetUserId = (int)($_SESSION['user_id'] ?? 0);
        } else {
            if (!$this->canAssignColleague()) {
                $this->forbid("Nemáš oprávnění přihlašovat kolegy do služeb.");
            }
        }

        $place = $this->ensurePlaceBelongsToCompany($placeId);

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $dateStr);
        if (!$dt) {
            $_SESSION['flash_error'] = "Neplatné datum.";
            $this->redirect('/services');
        }

        $dowIdx = (int)$dt->format('N') - 1;
        $dayKeys = array_keys(WeekDays::$days);
        $key = $dayKeys[$dowIdx] ?? null;
        $bit = $key ? (int)WeekDays::$days[$key] : 0;
        $mask = (int)($place['days_mask'] ?? 0);
        $open = $bit && (($mask & $bit) === $bit);

        if (!$open) {
            $_SESSION['flash_error'] = "Toto místo je v daný den zavřené.";
            $this->redirect('/services');
        }

        $holidayName = CzechHolidays::getHolidayName($dt);
        if (!empty($place['lock_on_holiday']) && $holidayName !== null) {
            $_SESSION['flash_error'] = "Na tomto místě nelze ve svátek zapsat službu (" . $holidayName . ").";
            $this->redirect('/services');
        }

        $db = DB::get();

        try {
            $stmt = $db->prepare("
                INSERT INTO service_assignments (company_id, place_id, service_date, user_id, assigned_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                Auth::companyId(),
                $placeId,
                $dateStr,
                $targetUserId,
                (int)($_SESSION['user_id'] ?? 0)
            ]);

            $_SESSION['flash_success'] = "Služba zapsána.";
        } catch (\PDOException $e) {
            if ((int)$e->getCode() === 23000) {
                $_SESSION['flash_error'] = "Tento termín už je obsazený.";
            } else {
                $_SESSION['flash_error'] = "Chyba při ukládání: " . $e->getMessage();
            }
        }

        $week = trim($_POST['_week'] ?? '');
        $this->redirect($week ? "/services?week=" . urlencode($week) : "/services");
    }

    public function unassign()
    {
        if (!Auth::check()) $this->redirect('/');
        $this->requireEnabled();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            $this->redirect('/services');
        }

        $placeId = (int)($_POST['place_id'] ?? 0);
        $dateStr = trim($_POST['service_date'] ?? '');

        if ($placeId <= 0 || $dateStr === '') {
            $_SESSION['flash_error'] = "Neplatná data.";
            $this->redirect('/services');
        }

        $this->ensurePlaceBelongsToCompany($placeId);

        $db = DB::get();

        $stmt = $db->prepare("
            SELECT user_id
            FROM service_assignments
            WHERE company_id = ? AND place_id = ? AND service_date = ?
            LIMIT 1
        ");
        $stmt->execute([Auth::companyId(), $placeId, $dateStr]);
        $row = $stmt->fetch();

        if (!$row) {
            $_SESSION['flash_error'] = "Tento termín není obsazený.";
            $this->redirect('/services');
        }

        $assignedUserId = (int)$row['user_id'];

        if (!$this->canUnassign($assignedUserId)) {
            $this->forbid("Nemáš oprávnění odhlásit tohoto uživatele ze služby.");
        }

        $stmt = $db->prepare("
            DELETE FROM service_assignments
            WHERE company_id = ? AND place_id = ? AND service_date = ?
            LIMIT 1
        ");
        $stmt->execute([Auth::companyId(), $placeId, $dateStr]);

        $_SESSION['flash_success'] = "Služba odhlášena.";

        $week = trim($_POST['_week'] ?? '');
        $this->redirect($week ? "/services?week=" . urlencode($week) : "/services");
    }
}
