<?php
return function (\PDO $db): void {
    foreach (['payroll_weekend_bonus_min_hours', 'payroll_holiday_bonus_min_hours'] as $column) {
        $stmt = $db->query("SHOW COLUMNS FROM companies LIKE '" . $column . "'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE companies ADD COLUMN `" . $column . "` DECIMAL(8,2) NOT NULL DEFAULT 0");
        }
    }
};
