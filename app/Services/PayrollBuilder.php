<?php

namespace Services;

use Core\CzechHolidays;
use Core\DB;

class PayrollBuilder
{
    private int $companyId;
    private int $year;
    private int $month;

    public function __construct(int $companyId, int $year, int $month)
    {
        $this->companyId = $companyId;
        $this->year = $year;
        $this->month = $month;
    }

    public function buildAllForPeriod(int $payrollPeriodId): void
    {
        $db = DB::get();

        foreach ($this->getPayrollUsers() as $user) {
            $snapshot = $this->buildUserSnapshot($user);
            $attendance = $this->buildAttendanceSnapshot((int)$user['id'], (float)($user['workload_hours'] ?? 0));
            $computed = $this->computePayroll($snapshot, $attendance);

            $stmt = $db->prepare("
                INSERT INTO payroll_items (
                    payroll_period_id, company_id, user_id,
                    user_name_snapshot,

                    payroll_salary_mode_snapshot,
                    payroll_fixed_salary_snapshot,
                    payroll_hourly_rate_snapshot,
                    workload_hours_snapshot,
                    payroll_fixed_salary_shortfall_mode_snapshot,

                    payroll_company_bonus_mode_snapshot,
                    payroll_custom_bonus_source_type_snapshot,
                    payroll_custom_bonus_source_id_snapshot,
                    payroll_custom_bonus_calc_type_snapshot,
                    payroll_custom_bonus_value_snapshot,

                    payroll_weekend_bonus_mode_snapshot,
                    payroll_weekend_bonus_type_snapshot,
                    payroll_weekend_bonus_value_snapshot,

                    payroll_holiday_bonus_mode_snapshot,
                    payroll_holiday_bonus_type_snapshot,
                    payroll_holiday_bonus_value_snapshot,

                    target_hours,
                    credited_hours,

                    work_hours,
                    weekend_hours,
                    holiday_hours,
                    weekend_shifts,
                    holiday_shifts,

                    vacation_hours,
                    ocr_hours,
                    sick_hours,

                    base_salary_amount,
                    company_bonus_amount,
                    weekend_bonus_amount,
                    holiday_bonus_amount,
                    personal_bonus_amount,
                    gross_total_amount,
                    note
                ) VALUES (
                    ?, ?, ?,
                    ?,

                    ?, ?, ?, ?, ?,

                    ?, ?, ?, ?, ?,

                    ?, ?, ?,

                    ?, ?, ?,

                    ?, ?,

                    ?, ?, ?, ?, ?,

                    ?, ?, ?,

                    ?, ?, ?, ?,
                    0, ?, NULL
                )
                ON DUPLICATE KEY UPDATE
                    user_name_snapshot = VALUES(user_name_snapshot),
                    payroll_salary_mode_snapshot = VALUES(payroll_salary_mode_snapshot),
                    payroll_fixed_salary_snapshot = VALUES(payroll_fixed_salary_snapshot),
                    payroll_hourly_rate_snapshot = VALUES(payroll_hourly_rate_snapshot),
                    workload_hours_snapshot = VALUES(workload_hours_snapshot),
                    payroll_fixed_salary_shortfall_mode_snapshot = VALUES(payroll_fixed_salary_shortfall_mode_snapshot),

                    payroll_company_bonus_mode_snapshot = VALUES(payroll_company_bonus_mode_snapshot),
                    payroll_custom_bonus_source_type_snapshot = VALUES(payroll_custom_bonus_source_type_snapshot),
                    payroll_custom_bonus_source_id_snapshot = VALUES(payroll_custom_bonus_source_id_snapshot),
                    payroll_custom_bonus_calc_type_snapshot = VALUES(payroll_custom_bonus_calc_type_snapshot),
                    payroll_custom_bonus_value_snapshot = VALUES(payroll_custom_bonus_value_snapshot),

                    payroll_weekend_bonus_mode_snapshot = VALUES(payroll_weekend_bonus_mode_snapshot),
                    payroll_weekend_bonus_type_snapshot = VALUES(payroll_weekend_bonus_type_snapshot),
                    payroll_weekend_bonus_value_snapshot = VALUES(payroll_weekend_bonus_value_snapshot),

                    payroll_holiday_bonus_mode_snapshot = VALUES(payroll_holiday_bonus_mode_snapshot),
                    payroll_holiday_bonus_type_snapshot = VALUES(payroll_holiday_bonus_type_snapshot),
                    payroll_holiday_bonus_value_snapshot = VALUES(payroll_holiday_bonus_value_snapshot),

                    target_hours = VALUES(target_hours),
                    credited_hours = VALUES(credited_hours),

                    work_hours = VALUES(work_hours),
                    weekend_hours = VALUES(weekend_hours),
                    holiday_hours = VALUES(holiday_hours),
                    weekend_shifts = VALUES(weekend_shifts),
                    holiday_shifts = VALUES(holiday_shifts),

                    vacation_hours = VALUES(vacation_hours),
                    ocr_hours = VALUES(ocr_hours),
                    sick_hours = VALUES(sick_hours),

                    base_salary_amount = VALUES(base_salary_amount),
                    company_bonus_amount = VALUES(company_bonus_amount),
                    weekend_bonus_amount = VALUES(weekend_bonus_amount),
                    holiday_bonus_amount = VALUES(holiday_bonus_amount),
                    gross_total_amount = VALUES(gross_total_amount)
            ");
            $stmt->execute([
                $payrollPeriodId,
                $this->companyId,
                (int)$user['id'],

                trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),

                $snapshot['salary_mode'],
                $snapshot['fixed_salary'],
                $snapshot['hourly_rate'],
                $snapshot['workload_hours'],
                $snapshot['fixed_salary_shortfall_mode'],

                $snapshot['company_bonus_mode'],
                $snapshot['custom_bonus_source_type'],
                $snapshot['custom_bonus_source_id'],
                $snapshot['custom_bonus_calc_type'],
                $snapshot['custom_bonus_value'],

                $snapshot['weekend_bonus_mode'],
                $snapshot['weekend_bonus_type'],
                $snapshot['weekend_bonus_value'],

                $snapshot['holiday_bonus_mode'],
                $snapshot['holiday_bonus_type'],
                $snapshot['holiday_bonus_value'],

                $attendance['target_hours'],
                $attendance['credited_hours'],

                $attendance['work_hours'],
                $attendance['weekend_hours'],
                $attendance['holiday_hours'],
                $attendance['weekend_shifts'],
                $attendance['holiday_shifts'],

                $attendance['vacation_hours'],
                $attendance['ocr_hours'],
                $attendance['sick_hours'],

                $computed['base_salary_amount'],
                $computed['company_bonus_amount'],
                $computed['weekend_bonus_amount'],
                $computed['holiday_bonus_amount'],
                $computed['gross_total_amount'],
            ]);

            $itemId = (int)$db->lastInsertId();
            if ($itemId === 0) {
                $stmt = $db->prepare("
                    SELECT id
                    FROM payroll_items
                    WHERE payroll_period_id = ?
                      AND user_id = ?
                    LIMIT 1
                ");
                $stmt->execute([$payrollPeriodId, (int)$user['id']]);
                $itemId = (int)$stmt->fetchColumn();
            }

            $this->replaceItemDays($itemId, $attendance['days']);
        }
    }

    public function rebuildSingleItem(
        int $payrollPeriodId,
        int $userId,
        float $existingPersonalBonus = 0.0,
        ?string $existingNote = null
    ): void {
        $db = DB::get();

        $stmt = $db->prepare("
            SELECT *
            FROM users
            WHERE company_id = ?
              AND id = ?
            LIMIT 1
        ");
        $stmt->execute([$this->companyId, $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return;
        }

        $snapshot = $this->buildUserSnapshot($user);
        $attendance = $this->buildAttendanceSnapshot((int)$user['id'], (float)($user['workload_hours'] ?? 0));
        $computed = $this->computePayroll($snapshot, $attendance);

        $grossTotal = $computed['base_salary_amount']
            + $computed['company_bonus_amount']
            + $computed['weekend_bonus_amount']
            + $computed['holiday_bonus_amount']
            + $existingPersonalBonus;

        $stmt = $db->prepare("
            UPDATE payroll_items
            SET user_name_snapshot = ?,

                payroll_salary_mode_snapshot = ?,
                payroll_fixed_salary_snapshot = ?,
                payroll_hourly_rate_snapshot = ?,
                workload_hours_snapshot = ?,
                payroll_fixed_salary_shortfall_mode_snapshot = ?,

                payroll_company_bonus_mode_snapshot = ?,
                payroll_custom_bonus_source_type_snapshot = ?,
                payroll_custom_bonus_source_id_snapshot = ?,
                payroll_custom_bonus_calc_type_snapshot = ?,
                payroll_custom_bonus_value_snapshot = ?,

                payroll_weekend_bonus_mode_snapshot = ?,
                payroll_weekend_bonus_type_snapshot = ?,
                payroll_weekend_bonus_value_snapshot = ?,

                payroll_holiday_bonus_mode_snapshot = ?,
                payroll_holiday_bonus_type_snapshot = ?,
                payroll_holiday_bonus_value_snapshot = ?,

                target_hours = ?,
                credited_hours = ?,

                work_hours = ?,
                weekend_hours = ?,
                holiday_hours = ?,
                weekend_shifts = ?,
                holiday_shifts = ?,

                vacation_hours = ?,
                ocr_hours = ?,
                sick_hours = ?,

                base_salary_amount = ?,
                company_bonus_amount = ?,
                weekend_bonus_amount = ?,
                holiday_bonus_amount = ?,
                gross_total_amount = ?,
                note = ?
            WHERE payroll_period_id = ?
              AND user_id = ?
        ");
        $stmt->execute([
            trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),

            $snapshot['salary_mode'],
            $snapshot['fixed_salary'],
            $snapshot['hourly_rate'],
            $snapshot['workload_hours'],
            $snapshot['fixed_salary_shortfall_mode'],

            $snapshot['company_bonus_mode'],
            $snapshot['custom_bonus_source_type'],
            $snapshot['custom_bonus_source_id'],
            $snapshot['custom_bonus_calc_type'],
            $snapshot['custom_bonus_value'],

            $snapshot['weekend_bonus_mode'],
            $snapshot['weekend_bonus_type'],
            $snapshot['weekend_bonus_value'],

            $snapshot['holiday_bonus_mode'],
            $snapshot['holiday_bonus_type'],
            $snapshot['holiday_bonus_value'],

            $attendance['target_hours'],
            $attendance['credited_hours'],

            $attendance['work_hours'],
            $attendance['weekend_hours'],
            $attendance['holiday_hours'],
            $attendance['weekend_shifts'],
            $attendance['holiday_shifts'],

            $attendance['vacation_hours'],
            $attendance['ocr_hours'],
            $attendance['sick_hours'],

            $computed['base_salary_amount'],
            $computed['company_bonus_amount'],
            $computed['weekend_bonus_amount'],
            $computed['holiday_bonus_amount'],
            round($grossTotal, 2),
            $existingNote !== '' ? $existingNote : null,

            $payrollPeriodId,
            $userId,
        ]);

        $stmt = $db->prepare("
            SELECT id
            FROM payroll_items
            WHERE payroll_period_id = ?
              AND user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$payrollPeriodId, $userId]);
        $itemId = (int)$stmt->fetchColumn();

        if ($itemId > 0) {
            $this->replaceItemDays($itemId, $attendance['days']);
        }
    }

    private function getPayrollUsers(): array
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT *
            FROM users
            WHERE company_id = ?
              AND status = 'active'
              AND COALESCE(payroll_active, 1) = 1
            ORDER BY last_name ASC, first_name ASC
        ");
        $stmt->execute([$this->companyId]);
        return $stmt->fetchAll() ?: [];
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
        $stmt->execute([$this->companyId]);
        return $stmt->fetch() ?: [];
    }

    private function buildUserSnapshot(array $user): array
    {
        $company = $this->getCompany();

        return [
            'salary_mode' => (string)($user['payroll_salary_mode'] ?? 'company_default'),
            'default_salary_type' => (string)($company['payroll_default_salary_type'] ?? 'fixed'),
            'fixed_salary' => $user['payroll_fixed_salary'] !== null ? (float)$user['payroll_fixed_salary'] : null,
            'hourly_rate' => $user['payroll_hourly_rate'] !== null ? (float)$user['payroll_hourly_rate'] : null,
            'workload_hours' => $user['workload_hours'] !== null ? (float)$user['workload_hours'] : null,
            'fixed_salary_shortfall_mode' => (string)($company['payroll_fixed_salary_shortfall_mode'] ?? 'full'),

            'company_bonus_mode' => (string)($user['payroll_company_bonus_mode'] ?? 'company_default'),
            'custom_bonus_source_type' => $user['payroll_custom_bonus_source_type'] ?: null,
            'custom_bonus_source_id' => $user['payroll_custom_bonus_source_id'] ? (int)$user['payroll_custom_bonus_source_id'] : null,
            'custom_bonus_calc_type' => $user['payroll_custom_bonus_calc_type'] ?: null,
            'custom_bonus_value' => $user['payroll_custom_bonus_value'] !== null ? (float)$user['payroll_custom_bonus_value'] : null,

            'company_default_bonus_enabled' => (int)($company['payroll_default_company_bonus_enabled'] ?? 0) === 1,
            'company_default_bonus_source_type' => $company['payroll_default_company_bonus_source_type'] ?: null,
            'company_default_bonus_source_id' => $company['payroll_default_company_bonus_source_id'] ? (int)$company['payroll_default_company_bonus_source_id'] : null,
            'company_default_bonus_calc_type' => $company['payroll_default_company_bonus_calc_type'] ?: null,
            'company_default_bonus_value' => ($company['payroll_default_company_bonus_value'] ?? null) !== null
                ? (float)$company['payroll_default_company_bonus_value']
                : null,

            'weekend_bonus_mode' => (string)($user['payroll_weekend_bonus_mode'] ?? 'company_default'),
            'weekend_bonus_type' => $user['payroll_weekend_bonus_type'] ?: (string)($company['payroll_weekend_bonus_type'] ?? 'shift_amount'),
            'weekend_bonus_value' => $user['payroll_weekend_bonus_value'] !== null
                ? (float)$user['payroll_weekend_bonus_value']
                : (float)($company['payroll_weekend_bonus_value'] ?? 0),

            'holiday_bonus_mode' => (string)($user['payroll_holiday_bonus_mode'] ?? 'company_default'),
            'holiday_bonus_type' => $user['payroll_holiday_bonus_type'] ?: (string)($company['payroll_holiday_bonus_type'] ?? 'shift_amount'),
            'holiday_bonus_value' => $user['payroll_holiday_bonus_value'] !== null
                ? (float)$user['payroll_holiday_bonus_value']
                : (float)($company['payroll_holiday_bonus_value'] ?? 0),
        ];
    }

    private function buildAttendanceSnapshot(int $userId, float $workloadHours): array
    {
        $from = sprintf('%04d-%02d-01', $this->year, $this->month);
        $to = date('Y-m-t', strtotime($from));

        $db = DB::get();
        $stmt = $db->prepare("
            SELECT *
            FROM attendance_records
            WHERE company_id = ?
              AND user_id = ?
              AND work_date BETWEEN ? AND ?
            ORDER BY work_date ASC, id ASC
        ");
        $stmt->execute([$this->companyId, $userId, $from, $to]);
        $rows = $stmt->fetchAll() ?: [];

        // Doplníme zákonné svátky, které připadají na pracovní den a nemají
        // vlastní záznam docházky. Jde o virtuální položky pouze pro výpočet.
        $rowsByDate = [];
        foreach ($rows as $row) {
            $rowsByDate[(string)$row['work_date']] = true;
        }
        foreach (CzechHolidays::getYearHolidays($this->year) as $holidayDate => $holidayName) {
            if (substr($holidayDate, 0, 7) !== sprintf('%04d-%02d', $this->year, $this->month)) {
                continue;
            }
            $holiday = new \DateTimeImmutable($holidayDate);
            if ((int)$holiday->format('N') > 5 || isset($rowsByDate[$holidayDate])) {
                continue;
            }
            $rows[] = [
                'work_date' => $holidayDate,
                'special_code' => 'S',
                'worked_minutes' => (int)round(max(0, $workloadHours) * 60),
            ];
        }
        usort($rows, static fn(array $a, array $b): int => strcmp((string)$a['work_date'], (string)$b['work_date']));

        $days = [];
        $workHours = 0.0;
        $weekendHours = 0.0;
        $holidayHours = 0.0;
        $weekendShifts = 0;
        $holidayShifts = 0;
        $vacationHours = 0.0;
        $ocrHours = 0.0;
        $sickHours = 0.0;
        $creditedHours = 0.0;

        foreach ($rows as $row) {
            $date = (string)$row['work_date'];
            $specialCode = strtoupper(trim((string)($row['special_code'] ?? '')));
            $hours = round(((int)($row['worked_minutes'] ?? 0)) / 60, 2);

            $dt = new \DateTimeImmutable($date);
            $isWeekend = in_array((int)$dt->format('N'), [6, 7], true);
            $isHoliday = CzechHolidays::isHoliday($date);

            if ($specialCode === 'D') {
                $vacationHours += $hours;
                $creditedHours += $hours;
                $days[] = [
                    'date' => $date,
                    'type' => 'vacation',
                    'hours' => $hours,
                    'label' => 'Dovolená',
                ];
                continue;
            }

            if ($specialCode === 'O') {
                $ocrHours += $hours;
                $creditedHours += $hours;
                $days[] = [
                    'date' => $date,
                    'type' => 'ocr',
                    'hours' => $hours,
                    'label' => 'OČR',
                ];
                continue;
            }

            if ($specialCode === 'PN') {
                $sickHours += $hours;
                $days[] = [
                    'date' => $date,
                    'type' => 'sick',
                    'hours' => $hours,
                    'label' => 'PN',
                ];
                continue;
            }

            if ($specialCode === 'S') {
                $creditedHours += $hours;
                $days[] = [
                    'date' => $date,
                    'type' => 'holiday_off',
                    'hours' => $hours,
                    'label' => 'Svátek',
                ];
                continue;
            }

            $workHours += $hours;
            $creditedHours += $hours;

            if ($isHoliday) {
                $holidayHours += $hours;
                $holidayShifts++;
                $days[] = [
                    'date' => $date,
                    'type' => 'holiday_work',
                    'hours' => $hours,
                    'label' => 'Práce ve svátek',
                ];
            } elseif ($isWeekend) {
                $weekendHours += $hours;
                $weekendShifts++;
                $days[] = [
                    'date' => $date,
                    'type' => 'weekend_work',
                    'hours' => $hours,
                    'label' => 'Práce o víkendu',
                ];
            } else {
                $days[] = [
                    'date' => $date,
                    'type' => 'work',
                    'hours' => $hours,
                    'label' => 'Práce',
                ];
            }
        }

        $targetHours = round($this->countWeekdaysInMonth() * max(0, $workloadHours), 2);

        return [
            'target_hours' => $targetHours,
            'credited_hours' => round($creditedHours, 2),

            'work_hours' => round($workHours, 2),
            'weekend_hours' => round($weekendHours, 2),
            'holiday_hours' => round($holidayHours, 2),
            'weekend_shifts' => $weekendShifts,
            'holiday_shifts' => $holidayShifts,

            'vacation_hours' => round($vacationHours, 2),
            'ocr_hours' => round($ocrHours, 2),
            'sick_hours' => round($sickHours, 2),

            'days' => $days,
        ];
    }

    private function countWeekdaysInMonth(): int
    {
        $from = new \DateTimeImmutable(sprintf('%04d-%02d-01', $this->year, $this->month));
        $to = new \DateTimeImmutable(date('Y-m-t', strtotime($from->format('Y-m-d'))));

        $count = 0;
        for ($d = $from; $d <= $to; $d = $d->modify('+1 day')) {
            $n = (int)$d->format('N');
            if ($n >= 1 && $n <= 5) {
                $count++;
            }
        }

        return $count;
    }

    private function computePayroll(array $snapshot, array $attendance): array
    {
        $salaryMode = $snapshot['salary_mode'] === 'company_default'
            ? $snapshot['default_salary_type']
            : $snapshot['salary_mode'];

        $fixedSalary = (float)($snapshot['fixed_salary'] ?? 0);
        $hourlyRate = (float)($snapshot['hourly_rate'] ?? 0);
        $targetHours = (float)$attendance['target_hours'];
        $creditedHours = (float)$attendance['credited_hours'];

        $fixedPart = 0.0;
        $shortfallMode = (string)($snapshot['fixed_salary_shortfall_mode'] ?? 'full');

        if (in_array($salaryMode, ['fixed', 'mixed'], true) && $fixedSalary > 0) {
            if ($shortfallMode === 'full') {
                $fixedPart = $fixedSalary;
            } elseif ($shortfallMode === 'proportional') {
                if ($targetHours > 0) {
                    $ratio = min(1, max(0, $creditedHours / $targetHours));
                    $fixedPart = $fixedSalary * $ratio;
                } else {
                    $fixedPart = $fixedSalary;
                }
            } elseif ($shortfallMode === 'zero') {
                if ($targetHours <= 0 || $creditedHours >= $targetHours) {
                    $fixedPart = $fixedSalary;
                } else {
                    $fixedPart = 0.0;
                }
            }
        }

        $hourlyPart = 0.0;
        if (in_array($salaryMode, ['hourly', 'mixed'], true) && $hourlyRate > 0) {
            $hourlyPart = $hourlyRate * (float)$attendance['work_hours'];
        }

        $base = $fixedPart + $hourlyPart;

        $companyBonus = $this->computeCompanyBonus($snapshot);
        $weekendBonus = $this->computeSpecialBonus(
            (string)$snapshot['weekend_bonus_mode'],
            (string)$snapshot['weekend_bonus_type'],
            (float)($snapshot['weekend_bonus_value'] ?? 0),
            $hourlyRate,
            (float)$attendance['weekend_hours'],
            (int)$attendance['weekend_shifts']
        );
        $holidayBonus = $this->computeSpecialBonus(
            (string)$snapshot['holiday_bonus_mode'],
            (string)$snapshot['holiday_bonus_type'],
            (float)($snapshot['holiday_bonus_value'] ?? 0),
            $hourlyRate,
            (float)$attendance['holiday_hours'],
            (int)$attendance['holiday_shifts']
        );

        $total = $base + $companyBonus + $weekendBonus + $holidayBonus;

        return [
            'base_salary_amount' => round($base, 2),
            'company_bonus_amount' => round($companyBonus, 2),
            'weekend_bonus_amount' => round($weekendBonus, 2),
            'holiday_bonus_amount' => round($holidayBonus, 2),
            'gross_total_amount' => round($total, 2),
        ];
    }

    private function computeCompanyBonus(array $snapshot): float
    {
        $mode = (string)$snapshot['company_bonus_mode'];

        if ($mode === 'none') {
            return 0.0;
        }

        if ($mode === 'custom') {
            $sourceType = $snapshot['custom_bonus_source_type'];
            $sourceId = $snapshot['custom_bonus_source_id'];
            $calcType = $snapshot['custom_bonus_calc_type'];
            $value = $snapshot['custom_bonus_value'];
        } else {
            if (!$snapshot['company_default_bonus_enabled']) {
                return 0.0;
            }

            $sourceType = $snapshot['company_default_bonus_source_type'];
            $sourceId = $snapshot['company_default_bonus_source_id'];
            $calcType = $snapshot['company_default_bonus_calc_type'];
            $value = $snapshot['company_default_bonus_value'];
        }

        if (!$sourceType || !$sourceId || !$calcType || $value === null) {
            return 0.0;
        }

        $economicValue = $this->loadEconomicValue((string)$sourceType, (int)$sourceId);
        if ($economicValue <= 0) {
            return 0.0;
        }

        if ($calcType === 'fixed') {
            return (float)$value;
        }

        return $economicValue * ((float)$value / 100);
    }

    private function loadEconomicValue(string $sourceType, int $sourceId): float
    {
        $periodKey = sprintf('%04d-%02d', $this->year, $this->month);
        $db = DB::get();

        if ($sourceType === 'indicator') {
            $stmt = $db->prepare("
                SELECT value
                FROM economic_indicator_values
                WHERE company_id = ?
                  AND definition_id = ?
                  AND period_key = ?
                LIMIT 1
            ");
            $stmt->execute([$this->companyId, $sourceId, $periodKey]);
            return (float)($stmt->fetchColumn() ?? 0);
        }

        $stmt = $db->prepare("
            SELECT id
            FROM economic_indicator_definitions
            WHERE company_id = ?
              AND parent_id = ?
              AND type = 'indicator'
        ");
        $stmt->execute([$this->companyId, $sourceId]);
        $ids = array_map('intval', array_column($stmt->fetchAll() ?: [], 'id'));

        if (empty($ids)) {
            return 0.0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$this->companyId, $periodKey], $ids);

        $stmt = $db->prepare("
            SELECT COALESCE(SUM(value), 0)
            FROM economic_indicator_values
            WHERE company_id = ?
              AND period_key = ?
              AND definition_id IN ($placeholders)
        ");
        $stmt->execute($params);

        return (float)($stmt->fetchColumn() ?? 0);
    }

    private function computeSpecialBonus(
        string $mode,
        string $type,
        float $value,
        float $hourlyRate,
        float $hours,
        int $shifts
    ): float {
        if ($mode === 'none') {
            return 0.0;
        }

        if ($type === 'shift_amount') {
            return $value * $shifts;
        }

        if ($type === 'hour_amount') {
            return $value * $hours;
        }

        if ($type === 'hourly_rate_percent') {
            return ($hourlyRate * ($value / 100)) * $hours;
        }

        return 0.0;
    }

    private function replaceItemDays(int $payrollItemId, array $days): void
    {
        $db = DB::get();

        $stmt = $db->prepare("
            DELETE FROM payroll_item_days
            WHERE payroll_item_id = ?
        ");
        $stmt->execute([$payrollItemId]);

        $stmt = $db->prepare("
            INSERT INTO payroll_item_days (payroll_item_id, company_id, day_date, day_type, hours, label)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($days as $day) {
            $stmt->execute([
                $payrollItemId,
                $this->companyId,
                $day['date'],
                $day['type'],
                $day['hours'],
                $day['label'] ?? null,
            ]);
        }
    }
}
