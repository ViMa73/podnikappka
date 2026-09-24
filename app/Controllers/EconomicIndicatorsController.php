<?php

namespace Controllers;

use Core\Auth;
use Core\DB;
use Core\CSRF;
use Core\Feature;

class EconomicIndicatorsController
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
        if (!Feature::enabled('economic_indicators')) {
            http_response_code(404);
            exit('Modul ekonomické ukazatele není zapnutý.');
        }
    }

    private function requireViewAccess(): void
    {
        if (!$this->canView()) {
            http_response_code(403);
            exit('Nemáš oprávnění.');
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

    private function canView(): bool
    {
        return in_array(Auth::role(), ['owner', 'manager'], true);
    }

    private function canManage(): bool
    {
        if (Auth::role() === 'owner') {
            return true;
        }

        if (Auth::role() === 'manager') {
            $db = DB::get();
            $stmt = $db->prepare("
                SELECT economic_indicators_allow_manager
                FROM companies
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([$this->companyId()]);
            return (int)$stmt->fetchColumn() === 1;
        }

        return false;
    }

    private function forbid(): void
    {
        http_response_code(403);
        exit('Nemáš oprávnění.');
    }

    private function normalizePeriodType(?string $value): ?string
    {
        $value = trim((string) $value);
        return in_array($value, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'], true) ? $value : null;
    }

    private function getDefinitions(): array
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT d.*
            FROM economic_indicator_definitions d
            WHERE d.company_id = ?
            ORDER BY
                COALESCE(d.parent_id, 0) ASC,
                d.sort_order ASC,
                d.id ASC
        ");
        $stmt->execute([$this->companyId()]);
        return $stmt->fetchAll() ?: [];
    }

    private function getDefinitionById(int $id): ?array
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT d.*
            FROM economic_indicator_definitions d
            WHERE d.company_id = ?
              AND d.id = ?
            LIMIT 1
        ");
        $stmt->execute([$this->companyId(), $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function getDefinitionsIndexed(): array
    {
        $rows = $this->getDefinitions();
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int)$row['id']] = $row;
        }
        return $indexed;
    }

    private function getEffectivePeriodType(array $definition, array $definitionsById): ?string
    {
        $own = $this->normalizePeriodType($definition['period_type'] ?? null);
        if ($own !== null) {
            return $own;
        }

        $parentId = (int)($definition['parent_id'] ?? 0);
        if ($parentId > 0 && isset($definitionsById[$parentId])) {
            return $this->normalizePeriodType($definitionsById[$parentId]['period_type'] ?? null);
        }

        return null;
    }

    private function getTopLevelStructures(array $definitionsById): array
    {
        $result = [];
        foreach ($definitionsById as $def) {
            if ((int)($def['parent_id'] ?? 0) !== 0) {
                continue;
            }

            $def['effective_period_type'] = $this->getEffectivePeriodType($def, $definitionsById);
            $result[] = $def;
        }

        usort($result, static function ($a, $b) {
            return [$a['sort_order'] ?? 0, $a['id']] <=> [$b['sort_order'] ?? 0, $b['id']];
        });

        return $result;
    }

    private function getIndicatorOptions(array $definitionsById): array
    {
        $result = [];

        foreach ($definitionsById as $def) {
            if (($def['type'] ?? '') !== 'indicator') {
                continue;
            }

            $effectivePeriodType = $this->getEffectivePeriodType($def, $definitionsById);
            if ($effectivePeriodType === null) {
                continue;
            }

            $label = (string)$def['name'];
            $parentId = (int)($def['parent_id'] ?? 0);

            if ($parentId > 0 && isset($definitionsById[$parentId])) {
                $label = $definitionsById[$parentId]['name'] . ' / ' . $label;
            }

            $def['effective_period_type'] = $effectivePeriodType;
            $def['select_label'] = $label;
            $result[] = $def;
        }

        usort($result, static function ($a, $b) {
            return strcmp((string)$a['select_label'], (string)$b['select_label']);
        });

        return $result;
    }

    private function getLeafChildrenIds(int $rootId, array $definitionsById): array
    {
        $result = [];

        $walk = function (int $parentId) use (&$walk, &$result, $definitionsById) {
            foreach ($definitionsById as $def) {
                if ((int)($def['parent_id'] ?? 0) !== $parentId) {
                    continue;
                }

                if (($def['type'] ?? '') === 'indicator') {
                    $result[] = (int)$def['id'];
                }

                $walk((int)$def['id']);
            }
        };

        $root = $definitionsById[$rootId] ?? null;
        if (!$root) {
            return [];
        }

        if (($root['type'] ?? '') === 'indicator') {
            return [$rootId];
        }

        $walk($rootId);

        return array_values(array_unique($result));
    }

    private function getRecentRecordsPage(int $page, int $perPage): array
    {
        $offset = max(0, ($page - 1) * $perPage);

        $db = DB::get();
        $stmt = $db->prepare("
            SELECT v.*,
                   d.name AS definition_name,
                   d.parent_id,
                   d.unit,
                   p.name AS parent_name,
                   cu.first_name AS created_by_first_name,
                   cu.last_name AS created_by_last_name,
                   uu.first_name AS updated_by_first_name,
                   uu.last_name AS updated_by_last_name
            FROM economic_indicator_values v
            JOIN economic_indicator_definitions d ON d.id = v.definition_id
            LEFT JOIN economic_indicator_definitions p ON p.id = d.parent_id
            LEFT JOIN users cu ON cu.id = v.created_by
            LEFT JOIN users uu ON uu.id = v.updated_by
            WHERE v.company_id = ?
            ORDER BY v.period_start_date DESC, v.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute([$this->companyId()]);
        return $stmt->fetchAll() ?: [];
    }

    private function getRecordsTotalCount(): int
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM economic_indicator_values
            WHERE company_id = ?
        ");
        $stmt->execute([$this->companyId()]);
        return (int)$stmt->fetchColumn();
    }

    private function buildPeriodMeta(string $periodType, array $input): array
    {
        switch ($periodType) {
            case 'daily':
                $date = trim((string)($input['period_date'] ?? ''));
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    throw new \RuntimeException('Vyber platný den.');
                }

                return [
                    'period_key' => $date,
                    'period_label' => date('j.n.Y', strtotime($date)),
                    'period_start_date' => $date,
                    'period_end_date' => $date,
                ];

            case 'weekly':
                $weekValue = trim((string)($input['period_week'] ?? ''));
                if (!preg_match('/^\d{4}-W\d{2}$/', $weekValue)) {
                    throw new \RuntimeException('Vyber platný týden.');
                }

                [$year, $week] = explode('-W', $weekValue);
                $year = (int)$year;
                $week = (int)$week;

                $dt = new \DateTimeImmutable();
                $start = $dt->setISODate($year, $week)->format('Y-m-d');
                $end = $dt->setISODate($year, $week, 7)->format('Y-m-d');

                return [
                    'period_key' => sprintf('%04d-W%02d', $year, $week),
                    'period_label' => sprintf('Týden %02d/%04d', $week, $year),
                    'period_start_date' => $start,
                    'period_end_date' => $end,
                ];

            case 'monthly':
                $monthValue = trim((string)($input['period_month'] ?? ''));
                if (!preg_match('/^\d{4}-\d{2}$/', $monthValue)) {
                    throw new \RuntimeException('Vyber platný měsíc.');
                }

                [$year, $month] = explode('-', $monthValue);
                $year = (int)$year;
                $month = (int)$month;

                $start = sprintf('%04d-%02d-01', $year, $month);
                $end = date('Y-m-t', strtotime($start));

                return [
                    'period_key' => sprintf('%04d-%02d', $year, $month),
                    'period_label' => sprintf('%02d/%04d', $month, $year),
                    'period_start_date' => $start,
                    'period_end_date' => $end,
                ];

            case 'quarterly':
                $year = (int)($input['period_quarter_year'] ?? 0);
                $quarter = (int)($input['period_quarter'] ?? 0);

                if ($year < 2000 || $year > 2100 || !in_array($quarter, [1, 2, 3, 4], true)) {
                    throw new \RuntimeException('Vyber platné kvartální období.');
                }

                $startMonth = (($quarter - 1) * 3) + 1;
                $start = sprintf('%04d-%02d-01', $year, $startMonth);
                $end = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $startMonth + 2)));

                return [
                    'period_key' => sprintf('%04d-Q%d', $year, $quarter),
                    'period_label' => sprintf('Q%d %04d', $quarter, $year),
                    'period_start_date' => $start,
                    'period_end_date' => $end,
                ];

            case 'yearly':
                $year = (int)($input['period_year'] ?? 0);
                if ($year < 2000 || $year > 2100) {
                    throw new \RuntimeException('Vyber platný rok.');
                }

                return [
                    'period_key' => (string)$year,
                    'period_label' => (string)$year,
                    'period_start_date' => sprintf('%04d-01-01', $year),
                    'period_end_date' => sprintf('%04d-12-31', $year),
                ];
        }

        throw new \RuntimeException('Neplatný typ období.');
    }

    private function rangeConfig(string $periodType): array
    {
        return match ($periodType) {
            'daily' => ['points' => 30],
            'weekly' => ['points' => 12],
            'monthly' => ['points' => 12],
            'quarterly' => ['points' => 8],
            'yearly' => ['points' => 5],
            default => ['points' => 12],
        };
    }

    private function buildTimeline(string $periodType, int $points): array
    {
        $timeline = [];
        $today = new \DateTimeImmutable('today');

        switch ($periodType) {
            case 'daily':
                for ($i = $points - 1; $i >= 0; $i--) {
                    $d = $today->modify("-{$i} days");
                    $timeline[] = [
                        'key' => $d->format('Y-m-d'),
                        'label' => $d->format('j.n.'),
                    ];
                }
                break;

            case 'weekly':
                $base = $today->modify('monday this week');
                for ($i = $points - 1; $i >= 0; $i--) {
                    $d = $base->modify("-{$i} weeks");
                    $year = (int)$d->format('o');
                    $week = (int)$d->format('W');
                    $timeline[] = [
                        'key' => sprintf('%04d-W%02d', $year, $week),
                        'label' => sprintf('T%02d/%s', $week, substr((string)$year, 2)),
                    ];
                }
                break;

            case 'monthly':
                $base = $today->modify('first day of this month');
                for ($i = $points - 1; $i >= 0; $i--) {
                    $d = $base->modify("-{$i} months");
                    $timeline[] = [
                        'key' => $d->format('Y-m'),
                        'label' => $d->format('m/Y'),
                    ];
                }
                break;

            case 'quarterly':
                $currentMonth = (int)$today->format('n');
                $currentQuarter = (int)ceil($currentMonth / 3);
                $year = (int)$today->format('Y');

                for ($i = $points - 1; $i >= 0; $i--) {
                    $q = $currentQuarter;
                    $y = $year;

                    for ($j = 0; $j < $i; $j++) {
                        $q--;
                        if ($q < 1) {
                            $q = 4;
                            $y--;
                        }
                    }

                    $timeline[] = [
                        'key' => sprintf('%04d-Q%d', $y, $q),
                        'label' => sprintf('Q%d %d', $q, $y),
                    ];
                }

                $timeline = array_reverse($timeline);
                break;

            case 'yearly':
                $year = (int)$today->format('Y');
                for ($i = $points - 1; $i >= 0; $i--) {
                    $y = $year - $i;
                    $timeline[] = [
                        'key' => (string)$y,
                        'label' => (string)$y,
                    ];
                }
                break;
        }

        return $timeline;
    }

    private function buildTrendData(array $data): array
    {
        $n = count($data);
        if ($n < 2) {
            return $data;
        }

        $sumX = 0.0;
        $sumY = 0.0;
        $sumXY = 0.0;
        $sumXX = 0.0;

        foreach ($data as $i => $y) {
            $x = (float)$i;
            $y = (float)$y;

            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumXX += $x * $x;
        }

        $denominator = ($n * $sumXX) - ($sumX * $sumX);
        if (abs($denominator) < 0.00001) {
            return $data;
        }

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        $trend = [];
        foreach ($data as $i => $_) {
            $trend[] = round($intercept + ($slope * $i), 2);
        }

        return $trend;
    }

    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    public function index(): void
    {
        $this->requireAccess();
        $this->requireFeature();
        $this->requireViewAccess();

        $definitionsById = $this->getDefinitionsIndexed();
        $topLevelStructures = $this->getTopLevelStructures($definitionsById);
        $indicatorOptions = $this->getIndicatorOptions($definitionsById);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $totalRecords = $this->getRecordsTotalCount();
        $totalPages = max(1, (int)ceil($totalRecords / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $records = $this->getRecentRecordsPage($page, $perPage);

        $view = 'economic-indicators/index';
        $title = 'Ekonomické ukazatele';

        require __DIR__ . '/../Views/layout.php';
    }

    public function chartData(): void
    {
        $this->requireAccess();
        $this->requireFeature();
        $this->requireViewAccess();

        $id = (int)($_GET['id'] ?? 0);
        $definitionsById = $this->getDefinitionsIndexed();
        $root = $definitionsById[$id] ?? null;

        if (!$root || (int)($root['parent_id'] ?? 0) !== 0) {
            $this->jsonResponse(['ok' => false, 'message' => 'Položka nebyla nalezena.'], 404);
        }

        $periodType = $this->getEffectivePeriodType($root, $definitionsById);
        if ($periodType === null) {
            $this->jsonResponse(['ok' => false, 'message' => 'Položka nemá definované období.'], 422);
        }

        $definitionIds = $this->getLeafChildrenIds($id, $definitionsById);
        if (empty($definitionIds)) {
            $this->jsonResponse([
                'ok' => true,
                'labels' => [],
                'datasets' => [],
                'title' => $root['name'],
            ]);
        }

        $range = $this->rangeConfig($periodType);
        $timeline = $this->buildTimeline($periodType, $range['points']);
        $timelineKeys = array_column($timeline, 'key');
        $labels = array_column($timeline, 'label');

        $placeholders = implode(',', array_fill(0, count($definitionIds), '?'));
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT definition_id, period_key, value
            FROM economic_indicator_values
            WHERE company_id = ?
              AND definition_id IN ($placeholders)
        ");
        $stmt->execute(array_merge([$this->companyId()], $definitionIds));
        $rows = $stmt->fetchAll() ?: [];

        $valuesMap = [];
        foreach ($rows as $row) {
            $defId = (int)$row['definition_id'];
            $pKey = (string)$row['period_key'];
            $valuesMap[$defId][$pKey] = (float)$row['value'];
        }

        $datasets = [];

        if (($root['type'] ?? '') === 'group') {
            $sumData = array_fill(0, count($timelineKeys), 0);

            foreach ($definitionIds as $defId) {
                $def = $definitionsById[$defId] ?? null;
                if (!$def) {
                    continue;
                }

                $data = [];
                foreach ($timelineKeys as $idx => $key) {
                    $v = (float)($valuesMap[$defId][$key] ?? 0);
                    $data[] = $v;
                    $sumData[$idx] += $v;
                }

                $datasets[] = [
                    'label' => (string)$def['name'],
                    'data' => $data,
                    'trend_data' => $this->buildTrendData($data),
                ];
            }

            $datasets[] = [
                'label' => 'Součet',
                'data' => $sumData,
                'trend_data' => $this->buildTrendData($sumData),
            ];
        } else {
            $defId = $definitionIds[0];
            $data = [];
            foreach ($timelineKeys as $key) {
                $data[] = (float)($valuesMap[$defId][$key] ?? 0);
            }

            $datasets[] = [
                'label' => (string)$root['name'],
                'data' => $data,
                'trend_data' => $this->buildTrendData($data),
            ];
        }

        $this->jsonResponse([
            'ok' => true,
            'title' => (string)$root['name'],
            'labels' => $labels,
            'datasets' => $datasets,
            'period_type' => $periodType,
        ]);
    }

    public function store(): void
    {
        $this->requireAccess();
        $this->requireFeature();
        $this->requireViewAccess();

        if (!$this->canManage()) {
            $this->forbid();
        }

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            header('Location: /economic-indicators');
            exit;
        }

        $definitionId = (int)($_POST['definition_id'] ?? 0);
        $definition = $this->getDefinitionById($definitionId);

        if (!$definition || ($definition['type'] ?? '') !== 'indicator') {
            $_SESSION['flash_error'] = 'Vyber platný ukazatel.';
            header('Location: /economic-indicators');
            exit;
        }

        $definitionsById = $this->getDefinitionsIndexed();
        $periodType = $this->getEffectivePeriodType($definition, $definitionsById);

        if ($periodType === null) {
            $_SESSION['flash_error'] = 'Ukazatel nemá definované období.';
            header('Location: /economic-indicators');
            exit;
        }

        $valueRaw = str_replace(',', '.', trim((string)($_POST['value'] ?? '')));
        if ($valueRaw === '' || !is_numeric($valueRaw)) {
            $_SESSION['flash_error'] = 'Vyplň platnou hodnotu.';
            header('Location: /economic-indicators');
            exit;
        }

        try {
            $periodMeta = $this->buildPeriodMeta($periodType, $_POST);
        } catch (\RuntimeException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: /economic-indicators');
            exit;
        }

        $db = DB::get();

        $stmt = $db->prepare("
            SELECT id
            FROM economic_indicator_values
            WHERE company_id = ?
              AND definition_id = ?
              AND period_key = ?
            LIMIT 1
        ");
        $stmt->execute([
            $this->companyId(),
            $definitionId,
            $periodMeta['period_key'],
        ]);

        if ($stmt->fetch()) {
            $_SESSION['flash_error'] = 'Pro zvolený ukazatel a období už záznam existuje.';
            header('Location: /economic-indicators');
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO economic_indicator_values (
                company_id, definition_id, period_type, period_key, period_label,
                period_start_date, period_end_date, value, note, created_by, updated_by
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $this->companyId(),
            $definitionId,
            $periodType,
            $periodMeta['period_key'],
            $periodMeta['period_label'],
            $periodMeta['period_start_date'],
            $periodMeta['period_end_date'],
            (float)$valueRaw,
            trim((string)($_POST['note'] ?? '')) ?: null,
            $this->userId(),
            $this->userId(),
        ]);

        $_SESSION['flash_success'] = 'Záznam byl uložen.';
        header('Location: /economic-indicators?page=1');
        exit;
    }

    public function update(): void
    {
        $this->requireAccess();
        $this->requireFeature();
        $this->requireViewAccess();

        if (!$this->canManage()) {
            $this->forbid();
        }

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            header('Location: /economic-indicators');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);

        $db = DB::get();
        $stmt = $db->prepare("
            SELECT *
            FROM economic_indicator_values
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$id, $this->companyId()]);
        $record = $stmt->fetch();

        if (!$record) {
            $_SESSION['flash_error'] = 'Záznam nebyl nalezen.';
            header('Location: /economic-indicators');
            exit;
        }

        $definitionId = (int)($_POST['definition_id'] ?? 0);
        $definition = $this->getDefinitionById($definitionId);

        if (!$definition || ($definition['type'] ?? '') !== 'indicator') {
            $_SESSION['flash_error'] = 'Vyber platný ukazatel.';
            header('Location: /economic-indicators');
            exit;
        }

        $definitionsById = $this->getDefinitionsIndexed();
        $periodType = $this->getEffectivePeriodType($definition, $definitionsById);

        if ($periodType === null) {
            $_SESSION['flash_error'] = 'Ukazatel nemá definované období.';
            header('Location: /economic-indicators');
            exit;
        }

        $valueRaw = str_replace(',', '.', trim((string)($_POST['value'] ?? '')));
        if ($valueRaw === '' || !is_numeric($valueRaw)) {
            $_SESSION['flash_error'] = 'Vyplň platnou hodnotu.';
            header('Location: /economic-indicators');
            exit;
        }

        try {
            $periodMeta = $this->buildPeriodMeta($periodType, $_POST);
        } catch (\RuntimeException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: /economic-indicators');
            exit;
        }

        $stmt = $db->prepare("
            SELECT id
            FROM economic_indicator_values
            WHERE company_id = ?
              AND definition_id = ?
              AND period_key = ?
              AND id <> ?
            LIMIT 1
        ");
        $stmt->execute([
            $this->companyId(),
            $definitionId,
            $periodMeta['period_key'],
            $id,
        ]);

        if ($stmt->fetch()) {
            $_SESSION['flash_error'] = 'Pro zvolený ukazatel a období už záznam existuje.';
            header('Location: /economic-indicators');
            exit;
        }

        $stmt = $db->prepare("
            UPDATE economic_indicator_values
            SET definition_id = ?,
                period_type = ?,
                period_key = ?,
                period_label = ?,
                period_start_date = ?,
                period_end_date = ?,
                value = ?,
                note = ?,
                updated_by = ?
            WHERE id = ?
              AND company_id = ?
        ");
        $stmt->execute([
            $definitionId,
            $periodType,
            $periodMeta['period_key'],
            $periodMeta['period_label'],
            $periodMeta['period_start_date'],
            $periodMeta['period_end_date'],
            (float)$valueRaw,
            trim((string)($_POST['note'] ?? '')) ?: null,
            $this->userId(),
            $id,
            $this->companyId(),
        ]);

        $_SESSION['flash_success'] = 'Záznam byl upraven.';
        header('Location: /economic-indicators');
        exit;
    }

    public function delete(): void
    {
        $this->requireAccess();
        $this->requireFeature();
        $this->requireViewAccess();

        if (!$this->canManage()) {
            $this->forbid();
        }

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            header('Location: /economic-indicators');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);

        $db = DB::get();
        $stmt = $db->prepare("
            DELETE FROM economic_indicator_values
            WHERE id = ? AND company_id = ?
            LIMIT 1
        ");
        $stmt->execute([$id, $this->companyId()]);

        $_SESSION['flash_success'] = 'Záznam byl smazán.';
        header('Location: /economic-indicators');
        exit;
    }
}
