<?php

namespace Core;

final class AttendanceMonthLock
{
    public static function isLocked(int $companyId, int $year, int $month): bool
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT id
            FROM attendance_month_locks
            WHERE company_id = ?
              AND year = ?
              AND month = ?
            LIMIT 1
        ");
        $stmt->execute([$companyId, $year, $month]);
        return (bool)$stmt->fetchColumn();
    }

    public static function lock(int $companyId, int $year, int $month, ?int $payrollPeriodId, ?int $createdBy): void
    {
        $db = DB::get();
        $stmt = $db->prepare("
            INSERT INTO attendance_month_locks (company_id, year, month, payroll_period_id, created_by)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                payroll_period_id = VALUES(payroll_period_id),
                created_by = VALUES(created_by)
        ");
        $stmt->execute([$companyId, $year, $month, $payrollPeriodId, $createdBy]);
    }

    public static function unlock(int $companyId, int $year, int $month): void
    {
        $db = DB::get();
        $stmt = $db->prepare("
            DELETE FROM attendance_month_locks
            WHERE company_id = ?
              AND year = ?
              AND month = ?
        ");
        $stmt->execute([$companyId, $year, $month]);
    }
}