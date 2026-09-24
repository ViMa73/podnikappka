<?php

namespace Controllers;

use Core\Auth;
use Core\DB;
use Core\CSRF;
use Dompdf\Dompdf;
use Dompdf\Options;

class WasteReportsController
{
    private function forbid()
    {
        http_response_code(403);
        $view = 'errors/403';
        $title = 'Přístup odepřen';
        require __DIR__ . '/../Views/layout.php';
        exit;
    }

    private function checkAccess()
    {
        if (!Auth::canAccessWasteReports()) {
            $this->forbid();
        }
    }

    private function redirect(string $url = '/waste-reports')
    {
        header("Location: {$url}");
        exit;
    }

    private function validateDecimal($value)
    {
        $value = str_replace(',', '.', trim((string)$value));

        if ($value === '') {
            return 0.000;
        }

        if (!preg_match('/^\d+(\.\d{1,3})?$/', $value)) {
            return false;
        }

        return number_format((float)$value, 3, '.', '');
    }

    private function companyId(): int
    {
        return (int)Auth::companyId();
    }

    private function ensurePlaceBelongsToCompany(int $placeId): array
    {
        $db = DB::get();

        $stmt = $db->prepare("
            SELECT *
            FROM waste_report_places
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$placeId, $this->companyId()]);
        $place = $stmt->fetch();

        if (!$place) {
            $_SESSION['flash_error'] = 'Vybrané místo svozu nebylo nalezeno.';
            $this->redirect();
        }

        return (array)$place;
    }

    private function ensureCollectionBelongsToCompany(int $id): array
    {
        $db = DB::get();

        $stmt = $db->prepare("
            SELECT *
            FROM waste_collections
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$id, $this->companyId()]);
        $row = $stmt->fetch();

        if (!$row) {
            $_SESSION['flash_error'] = 'Svoz nebyl nalezen.';
            $this->redirect();
        }

        return (array)$row;
    }

    public function index()
    {
        $this->checkAccess();

        $db = DB::get();

        $stmt = $db->prepare("
            SELECT *
            FROM waste_report_places
            WHERE company_id = ?
            ORDER BY pharmacy_name ASC
        ");
        $stmt->execute([$this->companyId()]);
        $places = $stmt->fetchAll();

        $stmt = $db->prepare("
            SELECT wc.*, p.pharmacy_name
            FROM waste_collections wc
            JOIN waste_report_places p ON p.id = wc.place_id
            WHERE wc.company_id = ?
            ORDER BY wc.collection_date DESC, wc.id DESC
        ");
        $stmt->execute([$this->companyId()]);
        $collections = $stmt->fetchAll();

        $currentYear = (int)date('Y');
        $exportYears = [];
        for ($y = $currentYear + 1; $y >= max(2020, $currentYear - 10); $y--) {
            $exportYears[] = $y;
        }

        $view = 'waste_reports/index';
        $title = 'Hlášení odpadů';
        require __DIR__ . '/../Views/layout.php';
    }

    public function createCollection()
    {
        $this->checkAccess();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            $this->redirect();
        }

        $collectionDate = trim((string)($_POST['collection_date'] ?? ''));
        $placeId = (int)($_POST['place_id'] ?? 0);

        if ($collectionDate === '' || $placeId <= 0) {
            $_SESSION['flash_error'] = 'Vyplň datum a místo svozu.';
            $this->redirect();
        }

        if (!\DateTimeImmutable::createFromFormat('Y-m-d', $collectionDate)) {
            $_SESSION['flash_error'] = 'Datum svozu není platné.';
            $this->redirect();
        }

        $this->ensurePlaceBelongsToCompany($placeId);

        $w131 = $this->validateDecimal($_POST['waste_200131'] ?? '');
        $w132 = $this->validateDecimal($_POST['waste_200132'] ?? '');

        if ($w131 === false || $w132 === false) {
            $_SESSION['flash_error'] = 'Hodnoty odpadů musí být čísla s maximálně 3 desetinnými místy.';
            $this->redirect();
        }

        $db = DB::get();
        $stmt = $db->prepare("
            INSERT INTO waste_collections
            (company_id, place_id, collection_date, waste_200131, waste_200132)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $this->companyId(),
            $placeId,
            $collectionDate,
            $w131,
            $w132
        ]);

        $_SESSION['flash_success'] = 'Svoz byl uložen.';
        $this->redirect();
    }

    public function updateCollection($id)
    {
        $this->checkAccess();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            $this->redirect();
        }

        $id = (int)$id;
        $this->ensureCollectionBelongsToCompany($id);

        $collectionDate = trim((string)($_POST['collection_date'] ?? ''));
        $placeId = (int)($_POST['place_id'] ?? 0);

        if ($collectionDate === '' || $placeId <= 0) {
            $_SESSION['flash_error'] = 'Vyplň datum a místo svozu.';
            $this->redirect();
        }

        if (!\DateTimeImmutable::createFromFormat('Y-m-d', $collectionDate)) {
            $_SESSION['flash_error'] = 'Datum svozu není platné.';
            $this->redirect();
        }

        $this->ensurePlaceBelongsToCompany($placeId);

        $w131 = $this->validateDecimal($_POST['waste_200131'] ?? '');
        $w132 = $this->validateDecimal($_POST['waste_200132'] ?? '');

        if ($w131 === false || $w132 === false) {
            $_SESSION['flash_error'] = 'Hodnoty odpadů musí být čísla s maximálně 3 desetinnými místy.';
            $this->redirect();
        }

        $db = DB::get();
        $stmt = $db->prepare("
            UPDATE waste_collections
            SET collection_date = ?,
                place_id = ?,
                waste_200131 = ?,
                waste_200132 = ?
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([
            $collectionDate,
            $placeId,
            $w131,
            $w132,
            $id,
            $this->companyId()
        ]);

        $_SESSION['flash_success'] = 'Svoz byl upraven.';
        $this->redirect();
    }

    public function deleteCollection($id)
    {
        $this->checkAccess();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            $this->redirect();
        }

        $id = (int)$id;
        $this->ensureCollectionBelongsToCompany($id);

        $db = DB::get();
        $stmt = $db->prepare("
            DELETE FROM waste_collections
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$id, $this->companyId()]);

        $_SESSION['flash_success'] = 'Svoz byl smazán.';
        $this->redirect();
    }

    public function exportPdf()
    {
        $this->checkAccess();

        $placeId = (int)($_GET['place_id'] ?? 0);
        $quarter = (int)($_GET['quarter'] ?? 0);
        $year    = (int)($_GET['year'] ?? date('Y'));

        if ($placeId <= 0 || !in_array($quarter, [1, 2, 3, 4], true) || $year < 2020 || $year > 2100) {
            $_SESSION['flash_error'] = 'Vyber platné místo, kvartál a rok.';
            $this->redirect();
        }

        $place = $this->ensurePlaceBelongsToCompany($placeId);

        $quarterMap = [
            1 => ['start' => "{$year}-01-01", 'end' => "{$year}-03-31", 'roman' => 'I'],
            2 => ['start' => "{$year}-04-01", 'end' => "{$year}-06-30", 'roman' => 'II'],
            3 => ['start' => "{$year}-07-01", 'end' => "{$year}-09-30", 'roman' => 'III'],
            4 => ['start' => "{$year}-10-01", 'end' => "{$year}-12-31", 'roman' => 'IV'],
        ];

        $period = $quarterMap[$quarter];

        $db = DB::get();

        $stmt = $db->prepare("
            SELECT wc.*
            FROM waste_collections wc
            WHERE wc.company_id = ?
              AND wc.place_id = ?
              AND wc.collection_date BETWEEN ? AND ?
            ORDER BY wc.collection_date ASC
        ");
        $stmt->execute([
            $this->companyId(),
            $placeId,
            $period['start'],
            $period['end']
        ]);
        $collections = $stmt->fetchAll();

        $sum131 = 0.0;
        $sum132 = 0.0;

        foreach ($collections as $row) {
            $sum131 += (float)($row['waste_200131'] ?? 0);
            $sum132 += (float)($row['waste_200132'] ?? 0);
        }

        $stmt = $db->prepare("
            SELECT name, ico
            FROM companies
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$this->companyId()]);
        $company = (array)($stmt->fetch() ?: []);

        $stmt = $db->prepare("
            SELECT first_name, last_name, email, phone
            FROM users
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([
            (int)($_SESSION['user_id'] ?? 0),
            $this->companyId()
        ]);
        $currentUser = (array)($stmt->fetch() ?: []);

        header('Content-Type: text/html; charset=utf-8');
        echo $this->buildWasteReportPrintHtml(
            $company,
            $place,
            $currentUser,
            $sum131,
            $sum132,
            $quarter,
            $year,
            $period['roman']
        );
        exit;
    }

    private function buildWasteReportPrintHtml(
        array $company,
        array $place,
        array $currentUser,
        float $sum131,
        float $sum132,
        int $quarter,
        int $year,
        string $quarterRoman
    ): string {
        $e = static function ($value): string {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        };

        $today = date('d.m.Y');
        $fullName = trim(((string)($currentUser['first_name'] ?? '')) . ' ' . ((string)($currentUser['last_name'] ?? '')));

        return '<!doctype html>
    <html lang="cs">
    <head>
    <meta charset="utf-8">
    <title>Hlášení odpadů</title>
    <style>
      @page {
        size: A4;
        margin: 16mm 14mm 16mm 14mm;
      }

      body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 12px;
        color: #111;
        line-height: 1.35;
        margin: 4em;
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
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 4px;
      }

      .subtitle {
        font-size: 12px;
      }

      .quarter-box {
        margin: 12px 0 16px 0;
        padding: 10px 12px;
        border: 1px solid #000;
        background: #f8f8f8;
        font-size: 13px;
      }

      .section-title {
        margin: 16px 0 6px 0;
        font-size: 13px;
        font-weight: bold;
      }

      table.meta,
      table.report,
      table.contact {
        width: 100%;
        border-collapse: collapse;
      }

      table.meta td,
      table.contact td,
      table.report th,
      table.report td {
        border: 1px solid #000;
        padding: 7px 8px;
        vertical-align: middle;
      }

      table.meta td.label,
      table.contact td.label {
        width: 34%;
        font-weight: bold;
        background: #f3f3f3;
      }

      table.report th {
        background: #ececec;
        font-weight: bold;
        text-align: left;
      }

      .num {
        text-align: right;
        white-space: nowrap;
      }

      .totals {
        margin-top: 10px;
        width: 100%;
        border-collapse: collapse;
      }

      .totals td {
        border: 1px solid #000;
        padding: 8px;
      }

      .totals td.label {
        font-weight: bold;
        background: #f3f3f3;
      }

      .totals td.num {
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

      .note {
        margin-top: 10px;
        font-size: 10px;
        color: #555;
      }

      @media print {
        .no-print {
          display: none;
        }
      }
    </style>
    </head>
    <body>
      <div class="page">

        <div class="header">
          <div class="title">Formulář pro předání údajů o množství odpadů léčiv z domácností</div>
          <div class="subtitle">Oznámení o odpadu léčiv z domácností, který byl předán do zařízení pro nakládání s odpady</div>
        </div>

        <div class="quarter-box">
          <strong>Oznámení za čtvrtletí:</strong> ' . $e($quarterRoman) . '
          &nbsp;&nbsp;&nbsp;
          <strong>Roku:</strong> ' . $e($year) . '
          &nbsp;&nbsp;&nbsp;
          <strong>Určeno krajskému úřadu</strong> ' . $e($place['regional_office'] ?? '') . '
        </div>

        <div class="section-title">Identifikace lékárny</div>
        <table class="meta">
          <tr>
            <td class="label">Identifikační číslo osoby (IČO)</td>
            <td>' . $e($company['ico'] ?? '') . '</td>
          </tr>
          <tr>
            <td class="label">Identifikační číslo provozovny (IČP)</td>
            <td>' . $e($place['icp'] ?? '') . '</td>
          </tr>
          <tr>
            <td class="label">Název lékárny</td>
            <td>' . $e($place['pharmacy_name'] ?? '') . '</td>
          </tr>
          <tr>
            <td class="label">Ulice a č.p.</td>
            <td>' . $e($place['street'] ?? '') . '</td>
          </tr>
          <tr>
            <td class="label">Obec</td>
            <td>' . $e($place['city'] ?? '') . '</td>
          </tr>
          <tr>
            <td class="label">PSČ</td>
            <td>' . $e($place['zip'] ?? '') . '</td>
          </tr>
          <tr>
            <td class="label">IČZÚJ</td>
            <td>' . $e($place['iczuj'] ?? '') . '</td>
          </tr>
        </table>

        <div class="section-title">Identifikace zařízení</div>
        <table class="meta">
          <tr>
            <td class="label">IČO osoby nakládající s odpady</td>
            <td>' . $e($place['waste_handler_ico'] ?? '') . '</td>
          </tr>
          <tr>
            <td class="label">Identifikační číslo zařízení (IČZ)</td>
            <td>' . $e($place['waste_facility_icz'] ?? '') . '</td>
          </tr>
        </table>

        <div class="section-title">Předané odpady léčiv z domácností</div>
        <table class="report">
          <thead>
            <tr>
              <th>Katalogové číslo</th>
              <th>Kategorie</th>
              <th>Název odpadu</th>
              <th>Množství odpadu (t)</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>20 01 31*</td>
              <td>N</td>
              <td>Nepoužitelná cytostatika</td>
              <td class="num">' . number_format($sum131, 3, ',', ' ') . '</td>
            </tr>
            <tr>
              <td>20 01 32*</td>
              <td>N</td>
              <td>Nepoužitelná léčiva od občanů</td>
              <td class="num">' . number_format($sum132, 3, ',', ' ') . '</td>
            </tr>
          </tbody>
        </table>

        <div class="section-title">Oznámení vyplnil</div>
        <table class="contact">
          <tr>
            <td class="label">Datum</td>
            <td>' . $e($today) . '</td>
          </tr>
          <tr>
            <td class="label">Jméno a příjmení</td>
            <td>' . $e($fullName) . '</td>
          </tr>
          <tr>
            <td class="label">Telefon</td>
            <td>' . $e($currentUser['phone'] ?? '') . '</td>
          </tr>
          <tr>
            <td class="label">Adresa elektronické pošty</td>
            <td>' . $e($currentUser['email'] ?? '') . '</td>
          </tr>
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
