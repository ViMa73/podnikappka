<?php
namespace Controllers;

use Core\Auth;
use Core\CSRF;
use Core\DB;
use Core\WeekDays;
use Core\Feature;

class TemperaturesController
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

        if (!Feature::enabled('temperatures')) {
            $_SESSION['flash_error'] = "Modul Teploty není zapnutý.";
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
            FROM temperature_places
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$placeId, $this->companyId()]);
        $place = $stmt->fetch();

        if (!$place) {
            $_SESSION['flash_error'] = "Vybraný teploměr nebyl nalezen.";
            $this->redirect('/temperatures');
        }

        return (array)$place;
    }

    private function getPlaceBelongsToCompanyOrNull(int $placeId): ?array
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT *
            FROM temperature_places
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$placeId, $this->companyId()]);
        $place = $stmt->fetch();

        return $place ? (array)$place : null;
    }

    private function normalizeRecordType(string $type): string
    {
        $type = trim($type);
        return in_array($type, ['temperature', 'humidity'], true) ? $type : '';
    }

    public function index(): void
    {
        $this->requireAccess();

        $db = DB::get();

        $stmt = $db->prepare("
            SELECT *
            FROM temperature_places
            WHERE company_id = ?
            ORDER BY id ASC
        ");
        $stmt->execute([$this->companyId()]);
        $places = $stmt->fetchAll();

        $dates = [];
        $today = new \DateTimeImmutable('today');

        $weekdayMap = [
            1 => 'mon',
            2 => 'tue',
            3 => 'wed',
            4 => 'thu',
            5 => 'fri',
            6 => 'sat',
            7 => 'sun',
        ];

        for ($i = 0; $i < 7; $i++) {
            $d = $today->modify("-{$i} days");
            $dayKey = $weekdayMap[(int)$d->format('N')];

            $dates[] = [
                'date' => $d,
                'dateY' => $d->format('Y-m-d'),
                'label' => $d->format('j.n.'),
                'day_name' => \Core\WeekDays::$names[$dayKey] ?? '',
                'day_label' => \Core\WeekDays::$labels[$dayKey] ?? '',
            ];
        }

        $fromDate = end($dates)['dateY'];
        $toDate = $dates[0]['dateY'];

        $stmt = $db->prepare("
            SELECT *
            FROM temperature_records
            WHERE company_id = ?
              AND record_date BETWEEN ? AND ?
        ");
        $stmt->execute([
            $this->companyId(),
            $fromDate,
            $toDate
        ]);
        $rows = $stmt->fetchAll();

        $records = [];
        foreach ($rows as $row) {
            $pid = (int)$row['place_id'];
            $dateY = (string)$row['record_date'];
            $type = (string)($row['record_type'] ?? 'temperature');

            $records[$pid][$type][$dateY] = [
                'value' => (float)$row['value_c'],
                'created_by' => (int)$row['created_by'],
                'updated_at' => $row['updated_at'] ?? null,
            ];
        }

        $tableRows = [];
        foreach ($places as $place) {
            $placeId = (int)$place['id'];
            $placeName = (string)$place['name'];
            $humidityEnabled = (int)($place['humidity_enabled'] ?? 0) === 1;

            $tableRows[] = [
                'place_id' => $placeId,
                'place_name' => $placeName,
                'record_type' => 'temperature',
                'label' => $placeName . ' - teplota',
            ];

            if ($humidityEnabled) {
                $tableRows[] = [
                    'place_id' => $placeId,
                    'place_name' => $placeName,
                    'record_type' => 'humidity',
                    'label' => $placeName . ' - vlhkost',
                ];
            }
        }

        $view = 'temperatures/index';
        $title = 'Teploty';
        require __DIR__ . '/../Views/layout.php';
    }

    public function save(): void
    {
        $this->requireAccess();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            $this->redirect('/temperatures');
        }

        $key = (string)($_POST['place_key'] ?? '');

        $placeId = 0;
        $recordType = '';

        if ($key !== '' && strpos($key, '|') !== false) {
            [$pid, $type] = explode('|', $key, 2);
            $placeId = (int)$pid;
            $recordType = $this->normalizeRecordType($type);
        }
        $recordDate = trim((string)($_POST['record_date'] ?? ''));
        $valueRaw = str_replace(',', '.', trim((string)($_POST['value_c'] ?? '')));

        if ($placeId <= 0) {
            $_SESSION['flash_error'] = "Vyber místo.";
            $this->redirect('/temperatures');
        }

        if ($recordDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $recordDate)) {
            $_SESSION['flash_error'] = "Vyplň platné datum.";
            $this->redirect('/temperatures');
        }

        if (!\DateTimeImmutable::createFromFormat('Y-m-d', $recordDate)) {
            $_SESSION['flash_error'] = "Vyplň platné datum.";
            $this->redirect('/temperatures');
        }

        if ($recordType === '') {
            $_SESSION['flash_error'] = "Neplatný typ záznamu.";
            $this->redirect('/temperatures');
        }

        $todayStr = date('Y-m-d');
        if ($recordDate > $todayStr) {
            $_SESSION['flash_error'] = "Hodnotu nelze evidovat do budoucnosti.";
            $this->redirect('/temperatures');
        }

        if ($valueRaw === '' || !is_numeric($valueRaw)) {
            $_SESSION['flash_error'] = "Hodnota musí být číslo.";
            $this->redirect('/temperatures');
        }

        $value = (float)$valueRaw;

        $place = $this->ensurePlaceBelongsToCompany($placeId);
        if ($recordType === 'humidity' && (int)($place['humidity_enabled'] ?? 0) !== 1) {
            $_SESSION['flash_error'] = "U tohoto místa není evidace vlhkosti povolena.";
            $this->redirect('/temperatures');
        }

        $db = DB::get();
        $stmt = $db->prepare("
            INSERT INTO temperature_records (
                company_id, place_id, record_date, record_type, value_c, created_by
            )
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                value_c = VALUES(value_c),
                created_by = VALUES(created_by)
        ");
        $stmt->execute([
            $this->companyId(),
            $placeId,
            $recordDate,
            $recordType,
            $value,
            $this->userId(),
        ]);

        $_SESSION['flash_success'] = "Hodnota byla uložena.";
        $this->redirect('/temperatures');
    }

    public function saveInline(): void
    {
        $this->requireAccess();

        header('Content-Type: application/json; charset=utf-8');

        try {
            if (!CSRF::check($_POST['_csrf'] ?? '')) {
                http_response_code(419);
                echo json_encode(['ok' => false, 'message' => 'Neplatný formulář (CSRF).']);
                exit;
            }

            $placeId = (int)($_POST['place_id'] ?? 0);
            $recordDate = trim((string)($_POST['record_date'] ?? ''));
            $recordType = $this->normalizeRecordType((string)($_POST['record_type'] ?? ''));
            $valueRaw = str_replace(',', '.', trim((string)($_POST['value_c'] ?? '')));

            if ($placeId <= 0 || $recordDate === '' || $recordType === '') {
                http_response_code(422);
                echo json_encode(['ok' => false, 'message' => 'Neplatná data.']);
                exit;
            }

            if (
                !preg_match('/^\d{4}-\d{2}-\d{2}$/', $recordDate) ||
                !\DateTimeImmutable::createFromFormat('Y-m-d', $recordDate)
            ) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'message' => 'Neplatné datum.']);
                exit;
            }

            if ($recordDate > date('Y-m-d')) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'message' => 'Nelze zapisovat do budoucnosti.']);
                exit;
            }

            if ($valueRaw === '' || !is_numeric($valueRaw)) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'message' => 'Hodnota musí být číslo.']);
                exit;
            }

            $value = (float)$valueRaw;

            $place = $this->getPlaceBelongsToCompanyOrNull($placeId);
            if (!$place) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'message' => 'Vybraný teploměr nebyl nalezen.']);
                exit;
            }

            if ($recordType === 'humidity' && (int)($place['humidity_enabled'] ?? 0) !== 1) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'message' => 'U tohoto místa není vlhkost povolena.']);
                exit;
            }

            $db = DB::get();
            $stmt = $db->prepare("
                INSERT INTO temperature_records (
                    company_id, place_id, record_date, record_type, value_c, created_by
                )
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    value_c = VALUES(value_c),
                    created_by = VALUES(created_by)
            ");
            $stmt->execute([
                $this->companyId(),
                $placeId,
                $recordDate,
                $recordType,
                $value,
                $this->userId(),
            ]);

            echo json_encode([
                'ok' => true,
                'formatted' => number_format($value, 2, ',', ' '),
                'formatted_raw' => number_format($value, 2, '.', '')
            ]);
            exit;

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'message' => 'DB chyba: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    public function dashboardData(): void
    {
        $this->requireAccess();

        header('Content-Type: application/json; charset=utf-8');

        try {
            $db = DB::get();
            $todayStr = date('Y-m-d');

            $stmt = $db->prepare("
                SELECT *
                FROM temperature_places
                WHERE company_id = ?
                ORDER BY id ASC
            ");
            $stmt->execute([$this->companyId()]);
            $places = $stmt->fetchAll() ?: [];

            $stmt = $db->prepare("
                SELECT *
                FROM temperature_records
                WHERE company_id = ?
                AND record_date = ?
            ");
            $stmt->execute([$this->companyId(), $todayStr]);
            $rows = $stmt->fetchAll() ?: [];

            $records = [];
            foreach ($rows as $row) {
                $pid = (int)$row['place_id'];
                $type = (string)($row['record_type'] ?? 'temperature');

                $records[$pid][$type] = [
                    'value' => (float)$row['value_c'],
                    'created_by' => (int)$row['created_by'],
                    'updated_at' => $row['updated_at'] ?? null,
                ];
            }

            $tableRows = [];
            foreach ($places as $place) {
                $placeId = (int)$place['id'];
                $placeName = (string)$place['name'];
                $humidityEnabled = (int)($place['humidity_enabled'] ?? 0) === 1;

                $tableRows[] = [
                    'place_id' => $placeId,
                    'place_name' => $placeName,
                    'record_type' => 'temperature',
                    'label' => $placeName . ' - teplota',
                    'value' => isset($records[$placeId]['temperature'])
                        ? number_format((float)$records[$placeId]['temperature']['value'], 2, '.', '')
                        : '',
                    'has_value' => isset($records[$placeId]['temperature']),
                ];

                if ($humidityEnabled) {
                    $tableRows[] = [
                        'place_id' => $placeId,
                        'place_name' => $placeName,
                        'record_type' => 'humidity',
                        'label' => $placeName . ' - vlhkost',
                        'value' => isset($records[$placeId]['humidity'])
                            ? number_format((float)$records[$placeId]['humidity']['value'], 2, '.', '')
                            : '',
                        'has_value' => isset($records[$placeId]['humidity']),
                    ];
                }
            }

            echo json_encode([
                'ok' => true,
                'today' => $todayStr,
                'rows' => $tableRows,
            ]);
            exit;

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'message' => 'Chyba při načítání teplot: ' . $e->getMessage(),
            ]);
            exit;
        }
    }
}
