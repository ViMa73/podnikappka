<?php
namespace Controllers;

use Core\Auth;
use Core\DB;
use Core\Feature;
use Core\CSRF;

class ExportsController
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

        $role = Auth::role();
        if ($role === 'owner') {
            return;
        }

        if ($role !== 'manager') {
            $_SESSION['flash_error'] = "Na tuto stránku nemáš přístup.";
            $this->redirect('/dashboard');
        }

        $db = DB::get();
        $stmt = $db->prepare("
            SELECT manager_can_view_exports
            FROM companies
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([Auth::companyId()]);
        $company = $stmt->fetch();

        $allowed = !empty($company['manager_can_view_exports']);
        if (!$allowed) {
            $_SESSION['flash_error'] = "Na tuto stránku nemáš přístup.";
            $this->redirect('/dashboard');
        }
    }

    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    private function normalizeTemperatureRecordType(string $value): string
    {
        $value = trim($value);
        return in_array($value, ['temperature', 'humidity'], true) ? $value : '';
    }

    private function normalizeMonth(string $value): ?string
    {
        $value = trim($value);
        if (!preg_match('/^\d{4}-\d{2}$/', $value)) {
            return null;
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m', $value);
        return $dt ? $value : null;
    }

    public function index(): void
    {
        $this->requireAccess();

        $db = DB::get();
        $stmt = $db->prepare("
            SELECT meal_voucher_export_enabled,
                   meal_voucher_min_hours
            FROM companies
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([Auth::companyId()]);
        $company = $stmt->fetch() ?: [];

        $mealVoucherExportEnabled = !empty($company['meal_voucher_export_enabled']);
        $mealVoucherMinHours = isset($company['meal_voucher_min_hours'])
            ? (float)$company['meal_voucher_min_hours']
            : 0.0;

        $attendanceEnabled = Feature::enabled('attendance');
        $temperaturesEnabled = Feature::enabled('temperatures');

        $temperatureExportOptions = [];
        if ($temperaturesEnabled) {
            $stmt = $db->prepare("
                SELECT id, name, humidity_enabled
                FROM temperature_places
                WHERE company_id = ?
                ORDER BY id ASC
            ");
            $stmt->execute([Auth::companyId()]);
            $places = $stmt->fetchAll() ?: [];

            foreach ($places as $place) {
                $placeId = (int)$place['id'];
                $name = (string)$place['name'];

                $temperatureExportOptions[] = [
                    'key' => $placeId . '|temperature',
                    'label' => $name . ' – teplota',
                ];

                if ((int)($place['humidity_enabled'] ?? 0) === 1) {
                    $temperatureExportOptions[] = [
                        'key' => $placeId . '|humidity',
                        'label' => $name . ' – vlhkost',
                    ];
                }
            }
        }

        $view = 'exports/index';
        $title = 'Exporty';
        require __DIR__ . '/../Views/layout.php';
    }

    public function mealVouchersPreview(): void
    {
        $this->requireAccess();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $this->jsonResponse([
                'ok' => false,
                'message' => 'Neplatný formulář (CSRF).'
            ], 419);
        }

        $db = DB::get();

        $stmt = $db->prepare("
            SELECT meal_voucher_export_enabled,
                   meal_voucher_min_hours
            FROM companies
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([Auth::companyId()]);
        $company = $stmt->fetch() ?: [];

        $mealVoucherExportEnabled = !empty($company['meal_voucher_export_enabled']);
        $mealVoucherMinHours = isset($company['meal_voucher_min_hours'])
            ? (float)$company['meal_voucher_min_hours']
            : 0.0;

        if (!$mealVoucherExportEnabled) {
            $this->jsonResponse([
                'ok' => false,
                'message' => 'Export stravenek není zapnutý.'
            ], 403);
        }

        if (!Feature::enabled('attendance')) {
            $this->jsonResponse([
                'ok' => false,
                'message' => 'Export stravenek nelze použít bez zapnutého modulu Docházka.'
            ], 422);
        }

        $month = $this->normalizeMonth((string)($_POST['month'] ?? ''));
        if (!$month) {
            $this->jsonResponse([
                'ok' => false,
                'message' => 'Vyber platný měsíc.'
            ], 422);
        }

        $from = \DateTimeImmutable::createFromFormat('Y-m', $month);
        $monthStart = $from->format('Y-m-01');
        $monthEnd = $from->modify('last day of this month')->format('Y-m-d');
        $minWorkedMinutes = (int) round($mealVoucherMinHours * 60);

        $stmt = $db->prepare("
            SELECT
                u.id,
                u.first_name,
                u.last_name,
                COALESCE(COUNT(ar.id), 0) AS voucher_days
            FROM users u
            LEFT JOIN attendance_records ar
                ON ar.user_id = u.id
               AND ar.company_id = u.company_id
               AND ar.work_date BETWEEN ? AND ?
               AND (ar.special_code IS NULL OR ar.special_code = '')
               AND ar.worked_minutes >= ?
            WHERE u.company_id = ?
              AND u.status = 'active'
            GROUP BY u.id, u.first_name, u.last_name
            ORDER BY u.last_name ASC, u.first_name ASC
        ");
        $stmt->execute([
            $monthStart,
            $monthEnd,
            $minWorkedMinutes,
            Auth::companyId()
        ]);

        $rows = $stmt->fetchAll() ?: [];

        $items = array_map(static function (array $row): array {
            $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            return [
                'user_id' => (int)($row['id'] ?? 0),
                'name' => $fullName !== '' ? $fullName : 'Bez jména',
                'voucher_days' => (int)($row['voucher_days'] ?? 0),
            ];
        }, $rows);

        $totalVouchers = 0;
        foreach ($items as $item) {
            $totalVouchers += (int)$item['voucher_days'];
        }

        $this->jsonResponse([
            'ok' => true,
            'month' => $month,
            'month_label' => $from->format('m/Y'),
            'min_hours' => number_format($mealVoucherMinHours, 2, ',', ' '),
            'items' => $items,
            'total_vouchers' => $totalVouchers,
        ]);
    }

    public function temperaturesPrint(): void
    {
        $this->requireAccess();

        if (!Feature::enabled('temperatures')) {
            $_SESSION['flash_error'] = "Modul Teploty není zapnutý.";
            $this->redirect('/exports');
        }

        $month = $this->normalizeMonth((string)($_GET['month'] ?? ''));
        if (!$month) {
            $_SESSION['flash_error'] = "Vyber platný měsíc exportu teplot.";
            $this->redirect('/exports');
        }

        $selectedKeys = $_GET['place_keys'] ?? [];
        if (!is_array($selectedKeys)) {
            $selectedKeys = [];
        }

        $selectedRows = [];
        foreach ($selectedKeys as $key) {
            $key = trim((string)$key);
            if ($key === '' || strpos($key, '|') === false) {
                continue;
            }

            [$pid, $type] = explode('|', $key, 2);
            $placeId = (int)$pid;
            $recordType = $this->normalizeTemperatureRecordType($type);

            if ($placeId <= 0 || $recordType === '') {
                continue;
            }

            $selectedRows[] = [
                'place_id' => $placeId,
                'record_type' => $recordType,
                'key' => $placeId . '|' . $recordType,
            ];
        }

        if (empty($selectedRows)) {
            $_SESSION['flash_error'] = "Vyber alespoň jeden teploměr nebo vlhkoměr.";
            $this->redirect('/exports');
        }

        $db = DB::get();

        $placeIds = array_values(array_unique(array_map(static fn($r) => (int)$r['place_id'], $selectedRows)));
        $placeholders = implode(',', array_fill(0, count($placeIds), '?'));

        $stmt = $db->prepare("
            SELECT id, name, humidity_enabled
            FROM temperature_places
            WHERE company_id = ?
              AND id IN ($placeholders)
            ORDER BY id ASC
        ");
        $stmt->execute(array_merge([Auth::companyId()], $placeIds));
        $places = $stmt->fetchAll() ?: [];

        $placesById = [];
        foreach ($places as $place) {
            $placesById[(int)$place['id']] = $place;
        }

        $validRows = [];
        foreach ($selectedRows as $row) {
            $place = $placesById[$row['place_id']] ?? null;
            if (!$place) {
                continue;
            }

            if ($row['record_type'] === 'humidity' && (int)($place['humidity_enabled'] ?? 0) !== 1) {
                continue;
            }

            $validRows[] = [
                'place_id' => (int)$row['place_id'],
                'record_type' => $row['record_type'],
                'label' => (string)$place['name'] . ($row['record_type'] === 'humidity' ? ' – vlhkost' : ' – teplota'),
                'key' => $row['key'],
            ];
        }

        if (empty($validRows)) {
            $_SESSION['flash_error'] = "Vybrané položky exportu nebyly nalezeny.";
            $this->redirect('/exports');
        }

        $monthDate = \DateTimeImmutable::createFromFormat('Y-m', $month);
        $monthStart = $monthDate->format('Y-m-01');
        $monthEnd = $monthDate->modify('last day of this month')->format('Y-m-d');

        $dates = [];
        $cursor = new \DateTimeImmutable($monthStart);
        $end = new \DateTimeImmutable($monthEnd);

        while ($cursor <= $end) {
            $dates[] = [
                'dateY' => $cursor->format('Y-m-d'),
                'label' => $cursor->format('d.m.'),
            ];
            $cursor = $cursor->modify('+1 day');
        }

        $stmt = $db->prepare("
            SELECT *
            FROM temperature_records
            WHERE company_id = ?
              AND record_date BETWEEN ? AND ?
              AND place_id IN ($placeholders)
        ");
        $stmt->execute(array_merge([Auth::companyId(), $monthStart, $monthEnd], $placeIds));
        $recordsRaw = $stmt->fetchAll() ?: [];

        $records = [];
        foreach ($recordsRaw as $record) {
            $pid = (int)$record['place_id'];
            $type = (string)($record['record_type'] ?? 'temperature');
            $dateY = (string)$record['record_date'];

            $records[$pid][$type][$dateY] = number_format((float)$record['value_c'], 2, ',', ' ');
        }

        $exportRows = [];
        foreach ($validRows as $row) {
            $values = [];
            foreach ($dates as $date) {
                $dateY = $date['dateY'];
                $values[$dateY] = $records[$row['place_id']][$row['record_type']][$dateY] ?? '';
            }

            $exportRows[] = [
                'label' => $row['label'],
                'record_type' => $row['record_type'],
                'values' => $values,
            ];
        }

        header('Content-Type: text/html; charset=utf-8');
        echo $this->buildTemperaturesPrintHtml(
            $monthDate->format('m/Y'),
            $dates,
            $exportRows
        );
        exit;
    }

    private function buildTemperaturesPrintHtml(string $monthLabel, array $dates, array $rows): string
    {
        $e = static function ($value): string {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        };

        $tableHead = '<tr><th>Místo / typ</th>';
        foreach ($dates as $date) {
            $tableHead .= '<th>' . $e($date['label']) . '</th>';
        }
        $tableHead .= '</tr>';

        $tableBody = '';
        foreach ($rows as $row) {
            $tableBody .= '<tr>';
            $tableBody .= '<td>' . $e($row['label']) . '</td>';

            foreach ($dates as $date) {
                $dateY = $date['dateY'];
                $tableBody .= '<td>' . $e($row['values'][$dateY] ?? '') . '</td>';
            }

            $tableBody .= '</tr>';
        }

        return '<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<title>Export teplot</title>
<style>
  @page {
    size: A4 landscape;
    margin: 8mm;
  }

  body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 12px;
    color: #111;
    line-height: 1.35;
    margin: 1em;
    background: #fff;
  }

  .page {
    width: 100%;
  }

  .header {
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
    margin-bottom: 14px;
  }

  .title {
    font-size: 22px;
    font-weight: bold;
    margin-bottom: 4px;
  }

  .subtitle {
    font-size: 13px;
    color: #444;
  }

  table.report {
    width: 100%;
    border-collapse: collapse;
    table-layout: auto;
    font-size: 10px;
  }

  table.report th,
  table.report td {
    border: 1px solid #000;
    padding: 5px 4px;
    text-align: center;
    vertical-align: middle;
  }

  table.report th:first-child,
  table.report td:first-child {
    text-align: left;
    white-space: nowrap;
    width: 1%;
    padding-left: 8px;
    font-weight: bold;
  }

  table.report thead th {
    background: #ececec;
    font-weight: bold;
  }

  .print-actions {
    margin-top: 18px;
    display: flex;
    gap: 10px;
  }

  .print-actions button {
    border: 1px solid #000;
    background: #fff;
    padding: 8px 14px;
    cursor: pointer;
    font-size: 12px;
  }

  @media print {
    .no-print {
      display: none !important;
    }
  }
</style>
</head>
<body>
  <div class="page">

    <div class="header">
      <div class="title">Export teplot</div>
      <div class="subtitle">Měsíc exportu: ' . $e($monthLabel) . '</div>
    </div>

    <table class="report">
      <thead>
        ' . $tableHead . '
      </thead>
      <tbody>
        ' . $tableBody . '
      </tbody>
    </table>

    <div class="print-actions no-print">
      <button type="button" onclick="window.print()">Tisk</button>
      <button type="button" onclick="closeSelf()">Zavřít</button>
    </div>

  </div>

  <script>
    function closeSelf() {
      try {
        if (window.opener && !window.opener.closed) {
          window.opener.focus();
        }
      } catch (e) {}
      window.close();
    }

    window.addEventListener("afterprint", function () {
      setTimeout(function () {
        closeSelf();
      }, 150);
    });

    window.addEventListener("load", function () {
      setTimeout(function () {
        window.print();
      }, 250);
    });
  </script>
</body>
</html>';
    }
}
