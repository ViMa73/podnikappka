<?php

return function (\PDO $db): void {
    $db->exec("
        CREATE TABLE IF NOT EXISTS attendance_month_locks (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            year SMALLINT(6) NOT NULL,
            month TINYINT(4) NOT NULL,
            payroll_period_id INT(11) DEFAULT NULL,
            created_by INT(11) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_attendance_month_lock (company_id, year, month),
            KEY idx_attendance_month_locks_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS attendance_month_lock_exceptions (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            year SMALLINT(6) NOT NULL,
            month TINYINT(4) NOT NULL,
            payroll_period_id INT(11) DEFAULT NULL,
            created_by INT(11) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_attendance_month_lock_exception (company_id, user_id, year, month),
            KEY idx_attendance_month_lock_exceptions_company (company_id, year, month)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS attendance_records (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            work_date DATE NOT NULL,
            special_code VARCHAR(10) DEFAULT NULL,
            arrival_time TIME DEFAULT NULL,
            departure_time TIME DEFAULT NULL,
            lunch_from TIME DEFAULT NULL,
            lunch_to TIME DEFAULT NULL,
            break_from TIME DEFAULT NULL,
            break_to TIME DEFAULT NULL,
            note TEXT DEFAULT NULL,
            worked_minutes INT(11) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_company_user_date (company_id, user_id, work_date),
            KEY idx_company_user_month (company_id, user_id, work_date),
            KEY fk_attendance_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS economic_indicator_definitions (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            parent_id INT(11) DEFAULT NULL,
            type ENUM('group','indicator') NOT NULL,
            name VARCHAR(255) NOT NULL,
            unit VARCHAR(50) DEFAULT NULL,
            period_type ENUM('daily','weekly','monthly','quarterly','yearly') DEFAULT NULL,
            sort_order INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_economic_indicator_definitions_company (company_id),
            KEY idx_economic_indicator_definitions_parent (parent_id),
            KEY idx_economic_indicator_definitions_sort (company_id, parent_id, sort_order, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS economic_indicator_values (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            definition_id INT(11) NOT NULL,
            period_type ENUM('daily','weekly','monthly','quarterly','yearly') NOT NULL,
            period_key VARCHAR(32) NOT NULL,
            period_label VARCHAR(64) NOT NULL,
            period_start_date DATE NOT NULL,
            period_end_date DATE NOT NULL,
            value DECIMAL(14,2) NOT NULL DEFAULT 0.00,
            note TEXT DEFAULT NULL,
            created_by INT(11) DEFAULT NULL,
            updated_by INT(11) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_economic_indicator_value (definition_id, period_key),
            KEY idx_economic_indicator_values_company (company_id),
            KEY idx_economic_indicator_values_definition (definition_id),
            KEY idx_economic_indicator_values_period_start (period_start_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS invitations (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            accepted TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY company_id (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS payroll_periods (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            year SMALLINT(6) NOT NULL,
            month TINYINT(4) NOT NULL,
            status ENUM('draft','approved') NOT NULL DEFAULT 'draft',
            attendance_locked TINYINT(1) NOT NULL DEFAULT 0,
            created_by INT(11) DEFAULT NULL,
            approved_by INT(11) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            approved_at DATETIME DEFAULT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_payroll_period (company_id, year, month),
            KEY idx_payroll_periods_company (company_id),
            KEY idx_payroll_periods_status (company_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS payroll_items (
            id INT(11) NOT NULL AUTO_INCREMENT,
            payroll_period_id INT(11) NOT NULL,
            company_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            user_name_snapshot VARCHAR(255) NOT NULL,
            payroll_salary_mode_snapshot ENUM('company_default','fixed','hourly','mixed') NOT NULL,
            payroll_fixed_salary_snapshot DECIMAL(10,2) DEFAULT NULL,
            payroll_hourly_rate_snapshot DECIMAL(10,2) DEFAULT NULL,
            workload_hours_snapshot DECIMAL(10,2) DEFAULT NULL,
            payroll_company_bonus_mode_snapshot ENUM('company_default','none','custom') NOT NULL,
            payroll_custom_bonus_source_type_snapshot ENUM('indicator','group') DEFAULT NULL,
            payroll_custom_bonus_source_id_snapshot INT(11) DEFAULT NULL,
            payroll_custom_bonus_calc_type_snapshot ENUM('percent','fixed') DEFAULT NULL,
            payroll_custom_bonus_value_snapshot DECIMAL(10,2) DEFAULT NULL,
            payroll_weekend_bonus_mode_snapshot ENUM('company_default','none','custom') NOT NULL,
            payroll_weekend_bonus_type_snapshot ENUM('shift_amount','hour_amount','hourly_rate_percent') DEFAULT NULL,
            payroll_weekend_bonus_value_snapshot DECIMAL(10,2) DEFAULT NULL,
            payroll_holiday_bonus_mode_snapshot ENUM('company_default','none','custom') NOT NULL,
            payroll_holiday_bonus_type_snapshot ENUM('shift_amount','hour_amount','hourly_rate_percent') DEFAULT NULL,
            payroll_holiday_bonus_value_snapshot DECIMAL(10,2) DEFAULT NULL,
            target_hours DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            credited_hours DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            work_hours DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            weekend_hours DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            holiday_hours DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            weekend_shifts INT(11) NOT NULL DEFAULT 0,
            holiday_shifts INT(11) NOT NULL DEFAULT 0,
            vacation_hours DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            ocr_hours DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            sick_hours DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            base_salary_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            company_bonus_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            weekend_bonus_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            holiday_bonus_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            personal_bonus_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            gross_total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            note TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            payroll_fixed_salary_shortfall_mode_snapshot ENUM('full','proportional','zero') NOT NULL DEFAULT 'full',
            PRIMARY KEY (id),
            UNIQUE KEY uniq_payroll_item_user (payroll_period_id, user_id),
            KEY idx_payroll_items_period (payroll_period_id),
            KEY idx_payroll_items_company_user (company_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS payroll_item_days (
            id INT(11) NOT NULL AUTO_INCREMENT,
            payroll_item_id INT(11) NOT NULL,
            company_id INT(11) NOT NULL,
            day_date DATE NOT NULL,
            day_type ENUM('work','weekend_work','holiday_work','holiday_off','vacation','ocr','sick') NOT NULL,
            hours DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            label VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_payroll_item_days_item (payroll_item_id),
            KEY idx_payroll_item_days_company_date (company_id, day_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS service_places (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            days_mask TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            notes_enabled TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS service_assignments (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            place_id INT(11) NOT NULL,
            service_date DATE NOT NULL,
            user_id INT(11) NOT NULL,
            assigned_by INT(11) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            note TEXT DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_place_day (place_id, service_date),
            KEY idx_company_date (company_id, service_date),
            KEY idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS service_day_notes (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            note_date DATE NOT NULL,
            note TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_company_user_day (company_id, user_id, note_date),
            KEY idx_company_date (company_id, note_date),
            KEY idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS sterilization_places (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            name VARCHAR(255) NOT NULL,
            is_sterilizer TINYINT(1) NOT NULL DEFAULT 0,
            is_drying TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_sterilization_places_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS sterilization_records (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            place_id INT(11) NOT NULL,
            record_date DATE NOT NULL,
            record_type ENUM('sterilization','drying') NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            quantity INT(11) NOT NULL DEFAULT 0,
            duration_minutes INT(11) NOT NULL DEFAULT 0,
            temperature_c DECIMAL(6,2) NOT NULL DEFAULT 0.00,
            created_by INT(11) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_ster_records_company (company_id),
            KEY idx_ster_records_place (place_id),
            KEY idx_ster_records_date (record_date),
            KEY fk_ster_records_user (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS tasks (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            assigned_user_id INT(11) NOT NULL,
            created_by INT(11) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            due_date DATE DEFAULT NULL,
            repeat_type ENUM('none','daily','weekly','monthly','quarterly','yearly') NOT NULL DEFAULT 'none',
            repeat_interval INT(11) NOT NULL DEFAULT 1,
            completed_at DATETIME DEFAULT NULL,
            completed_by INT(11) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_tasks_company (company_id),
            KEY idx_tasks_assigned_user (assigned_user_id),
            KEY idx_tasks_due_date (due_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS task_groups (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            title VARCHAR(255) NOT NULL,
            created_by INT(11) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            color VARCHAR(20) NOT NULL DEFAULT 'white',
            PRIMARY KEY (id),
            KEY idx_task_groups_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS task_group_items (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            group_id INT(11) NOT NULL,
            title VARCHAR(255) NOT NULL,
            sort_order INT(11) NOT NULL DEFAULT 0,
            completed_at DATETIME DEFAULT NULL,
            completed_by INT(11) DEFAULT NULL,
            created_by INT(11) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_task_group_items_company (company_id),
            KEY idx_task_group_items_group (group_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS temperature_places (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            name VARCHAR(255) NOT NULL,
            humidity_enabled TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_temperature_places_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS temperature_records (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            place_id INT(11) NOT NULL,
            record_date DATE NOT NULL,
            record_type ENUM('temperature','humidity') NOT NULL DEFAULT 'temperature',
            value_c DECIMAL(6,2) NOT NULL,
            created_by INT(11) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_temperature_record (company_id, place_id, record_date, record_type),
            KEY idx_temperature_records_place (place_id),
            KEY idx_temperature_records_date (record_date),
            KEY fk_temperature_records_user (created_by),
            KEY idx_temperature_records_company_place (company_id, place_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS vacations (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            title VARCHAR(255) NOT NULL,
            date_from DATE NOT NULL,
            date_to DATE NOT NULL,
            total_hours DECIMAL(8,2) DEFAULT NULL,
            note TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_vacations_company (company_id),
            KEY idx_vacations_user (user_id),
            KEY idx_vacations_dates (date_from, date_to)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS waste_report_places (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            pharmacy_name VARCHAR(255) NOT NULL,
            icp VARCHAR(50) DEFAULT NULL,
            street VARCHAR(255) DEFAULT NULL,
            city VARCHAR(255) DEFAULT NULL,
            zip VARCHAR(20) DEFAULT NULL,
            iczuj VARCHAR(50) DEFAULT NULL,
            regional_office VARCHAR(255) DEFAULT NULL,
            waste_handler_ico VARCHAR(50) DEFAULT NULL,
            waste_facility_icz VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_waste_report_places_company (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS waste_collections (
            id INT(11) NOT NULL AUTO_INCREMENT,
            company_id INT(11) NOT NULL,
            place_id INT(11) NOT NULL,
            collection_date DATE NOT NULL,
            waste_200131 DECIMAL(10,3) NOT NULL DEFAULT 0.000,
            waste_200132 DECIMAL(10,3) NOT NULL DEFAULT 0.000,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY company_id (company_id),
            KEY place_id (place_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci
    ");

    $foreignKeys = [
        "ALTER TABLE attendance_records ADD CONSTRAINT fk_attendance_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE",
        "ALTER TABLE attendance_records ADD CONSTRAINT fk_attendance_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE",

        "ALTER TABLE economic_indicator_definitions ADD CONSTRAINT fk_economic_indicator_definitions_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE",
        "ALTER TABLE economic_indicator_definitions ADD CONSTRAINT fk_economic_indicator_definitions_parent FOREIGN KEY (parent_id) REFERENCES economic_indicator_definitions (id) ON DELETE CASCADE",

        "ALTER TABLE invitations ADD CONSTRAINT invitations_ibfk_1 FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE",

        "ALTER TABLE service_places ADD CONSTRAINT fk_service_places_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE",
        "ALTER TABLE service_assignments ADD CONSTRAINT fk_sa_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE",
        "ALTER TABLE service_assignments ADD CONSTRAINT fk_sa_place FOREIGN KEY (place_id) REFERENCES service_places (id) ON DELETE CASCADE",
        "ALTER TABLE service_assignments ADD CONSTRAINT fk_sa_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE",
        "ALTER TABLE service_day_notes ADD CONSTRAINT fk_service_day_notes_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE",
        "ALTER TABLE service_day_notes ADD CONSTRAINT fk_service_day_notes_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE",

        "ALTER TABLE sterilization_places ADD CONSTRAINT fk_sterilization_places_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE",
        "ALTER TABLE sterilization_records ADD CONSTRAINT fk_ster_records_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE",
        "ALTER TABLE sterilization_records ADD CONSTRAINT fk_ster_records_place FOREIGN KEY (place_id) REFERENCES sterilization_places (id) ON DELETE CASCADE",
        "ALTER TABLE sterilization_records ADD CONSTRAINT fk_ster_records_user FOREIGN KEY (created_by) REFERENCES users (id)",

        "ALTER TABLE temperature_places ADD CONSTRAINT fk_temperature_places_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE",
        "ALTER TABLE temperature_records ADD CONSTRAINT fk_temperature_records_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE",
        "ALTER TABLE temperature_records ADD CONSTRAINT fk_temperature_records_place FOREIGN KEY (place_id) REFERENCES temperature_places (id) ON DELETE CASCADE",
        "ALTER TABLE temperature_records ADD CONSTRAINT fk_temperature_records_user FOREIGN KEY (created_by) REFERENCES users (id)",

        "ALTER TABLE vacations ADD CONSTRAINT fk_vacations_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE",
        "ALTER TABLE vacations ADD CONSTRAINT fk_vacations_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE",

        "ALTER TABLE waste_report_places ADD CONSTRAINT fk_waste_report_places_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE",
    ];

    foreach ($foreignKeys as $sql) {
        try {
            $db->exec($sql);
        } catch (\Throwable $e) {
            // FK už pravděpodobně existuje, ignorujeme
        }
    }
};
