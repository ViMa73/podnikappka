SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
/*!40101 SET NAMES utf8 */;

CREATE TABLE IF NOT EXISTS `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `ico` varchar(20) NOT NULL,
  `dic` varchar(20) DEFAULT NULL,
  `street` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `zip` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `allow_manager_settings` tinyint(1) NOT NULL DEFAULT 0,
  `manager_can_assign_services` tinyint(1) NOT NULL DEFAULT 1,
  `attendance_rounding_minutes` int(11) NOT NULL DEFAULT 15,
  `attendance_allow_break` tinyint(1) NOT NULL DEFAULT 0,
  `manager_can_view_waste_reports` tinyint(1) NOT NULL DEFAULT 0,
  `manager_can_view_exports` tinyint(1) NOT NULL DEFAULT 0,
  `meal_voucher_export_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `meal_voucher_min_hours` decimal(5,2) NOT NULL DEFAULT 6.00,
  `payroll_lock_attendance_after_creation` tinyint(1) NOT NULL DEFAULT 1,
  `payroll_allow_manager` tinyint(1) NOT NULL DEFAULT 0,
  `economic_indicators_allow_manager` tinyint(1) NOT NULL DEFAULT 0,
  `payroll_default_salary_type` enum('fixed','hourly','mixed') NOT NULL DEFAULT 'fixed',
  `payroll_component_fixed_salary` tinyint(1) NOT NULL DEFAULT 1,
  `payroll_component_hourly_wage` tinyint(1) NOT NULL DEFAULT 0,
  `payroll_component_company_bonus` tinyint(1) NOT NULL DEFAULT 0,
  `payroll_component_weekend_bonus` tinyint(1) NOT NULL DEFAULT 0,
  `payroll_component_holiday_bonus` tinyint(1) NOT NULL DEFAULT 0,
  `payroll_weekend_bonus_type` enum('shift_amount','hour_amount','hourly_rate_percent') NOT NULL DEFAULT 'shift_amount',
  `payroll_weekend_bonus_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payroll_holiday_bonus_type` enum('shift_amount','hour_amount','hourly_rate_percent') NOT NULL DEFAULT 'shift_amount',
  `payroll_holiday_bonus_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payroll_default_company_bonus_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `payroll_default_company_bonus_source_type` enum('indicator','group') DEFAULT NULL,
  `payroll_default_company_bonus_source_id` int(11) DEFAULT NULL,
  `payroll_default_company_bonus_calc_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `payroll_default_company_bonus_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payroll_rounding_type` enum('none','1','10','100') NOT NULL DEFAULT 'none',
  `payroll_count_overtime` tinyint(1) NOT NULL DEFAULT 0,
  `payroll_overlap_rule` enum('sum','higher','lower','holiday','weekend') NOT NULL DEFAULT 'sum',
  `payroll_fixed_salary_shortfall_mode` enum('full','proportional','zero') NOT NULL DEFAULT 'full',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `blocked_at` datetime DEFAULT NULL,
  `blocked_reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ico` (`ico`),
  UNIQUE KEY `uniq_companies_ico` (`ico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('owner','manager','worker') DEFAULT 'worker',
  `theme` varchar(20) DEFAULT 'light',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `avatar_path` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `invite_token` varchar(64) DEFAULT NULL,
  `invite_expires_at` datetime DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `verify_token` varchar(64) DEFAULT NULL,
  `verify_expires_at` datetime DEFAULT NULL,
  `workload_hours` decimal(4,2) DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires_at` datetime DEFAULT NULL,
  `vacation_hours_year` decimal(8,2) DEFAULT NULL,
  `dashboard_show_my_services` tinyint(1) NOT NULL DEFAULT 1,
  `payroll_active` tinyint(1) NOT NULL DEFAULT 1,
  `payroll_salary_mode` enum('company_default','fixed','hourly','mixed') NOT NULL DEFAULT 'company_default',
  `payroll_fixed_salary` decimal(10,2) DEFAULT NULL,
  `payroll_hourly_rate` decimal(10,2) DEFAULT NULL,
  `payroll_company_bonus_mode` enum('company_default','none','custom') NOT NULL DEFAULT 'company_default',
  `payroll_custom_bonus_source_type` enum('indicator','group') DEFAULT NULL,
  `payroll_custom_bonus_source_id` int(11) DEFAULT NULL,
  `payroll_custom_bonus_calc_type` enum('percent','fixed') DEFAULT NULL,
  `payroll_custom_bonus_value` decimal(10,2) DEFAULT NULL,
  `payroll_weekend_bonus_mode` enum('company_default','none','custom') NOT NULL DEFAULT 'company_default',
  `payroll_weekend_bonus_type` enum('shift_amount','hour_amount','hourly_rate_percent') DEFAULT NULL,
  `payroll_weekend_bonus_value` decimal(10,2) DEFAULT NULL,
  `payroll_holiday_bonus_mode` enum('company_default','none','custom') NOT NULL DEFAULT 'company_default',
  `payroll_holiday_bonus_type` enum('shift_amount','hour_amount','hourly_rate_percent') DEFAULT NULL,
  `payroll_holiday_bonus_value` decimal(10,2) DEFAULT NULL,
  `is_super_admin` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_invite_token` (`invite_token`),
  KEY `idx_users_company` (`company_id`),
  KEY `idx_users_verify_token` (`verify_token`),
  KEY `idx_users_reset_token` (`reset_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

ALTER TABLE users
ADD COLUMN birth_date DATE NULL AFTER phone;

CREATE TABLE IF NOT EXISTS `features` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

CREATE TABLE IF NOT EXISTS `company_features` (
  `company_id` int(11) NOT NULL,
  `feature_id` int(11) NOT NULL,
  `enabled` tinyint(1) DEFAULT 0,
  `value` text DEFAULT NULL,
  PRIMARY KEY (`company_id`,`feature_id`),
  KEY `feature_id` (`feature_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `executed_at` datetime NOT NULL,
  `executed_by_user_id` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'success',
  `message` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS user_dashboard_widgets (
  id INT(11) NOT NULL AUTO_INCREMENT,
  company_id INT(11) NOT NULL,
  user_id INT(11) NOT NULL,
  widget_key VARCHAR(100) NOT NULL,
  sort_order INT(11) NOT NULL DEFAULT 0,
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_user_widget (user_id, widget_key),
  KEY idx_user_sort (user_id, sort_order),
  KEY idx_company_user (company_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;




ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1`
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `company_features`
  ADD CONSTRAINT `company_features_ibfk_1`
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `company_features_ibfk_2`
  FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`) ON DELETE CASCADE;

INSERT IGNORE INTO features (name, description) VALUES
  ('services', 'Služby'),
  ('tasks', 'Úkoly'),
  ('vacations', 'Dovolené'),
  ('attendance', 'Docházka'),
  ('payrolls', 'Výplaty'),
  ('economic_indicators', 'Ekonomické údaje'),
  ('waste_reports', 'Hlášení odpadů'),
  ('temperatures', 'Teploty'),
  ('sterilization_drying', 'Sterilizace a sušení');



