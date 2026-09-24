<?php

namespace Core;

final class AttendanceMonthLockException
{
    public static function hasException(int $companyId, int $userId, int $year, int $month): bool
    {
        $db = DB::get();
        $stmt = $db->prepare("
            SELECT id
            FROM attendance_month_lock_exceptions
            WHERE company_id = ?
              AND user_id = ?
              AND year = ?
              AND month = ?
            LIMIT 1
        ");
        $stmt->execute([$companyId, $userId, $year, $month]);
        return (bool)$stmt->fetchColumn();
    }

    public static function allow(int $companyId, int $userId, int $year, int $month, ?int $payrollPeriodId, ?int $createdBy): void
    {
        $db = DB::get();
        $stmt = $db->prepare("
            INSERT INTO attendance_month_lock_exceptions (company_id, user_id, year, month, payroll_period_id, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                payroll_period_id = VALUES(payroll_period_id),
                created_by = VALUES(created_by)
        ");
        $stmt->execute([$companyId, $userId, $year, $month, $payrollPeriodId, $createdBy]);
    }

    public static function remove(int $companyId, int $userId, int $year, int $month): void
    {
        $db = DB::get();
        $stmt = $db->prepare("
            DELETE FROM attendance_month_lock_exceptions
            WHERE company_id = ?
              AND user_id = ?
              AND year = ?
              AND month = ?
        ");
        $stmt->execute([$companyId, $userId, $year, $month]);
    }
}
