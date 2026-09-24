<?php

return function (\PDO $db): void {
    // Open-source self-hosted edice už nepoužívá billing ani centrální právní souhlasy.
    $db->exec('SET FOREIGN_KEY_CHECKS=0');
    try {
        foreach (['billing_requests', 'billing_settings', 'subscriptions', 'legal_consents'] as $table) {
            $db->exec("DROP TABLE IF EXISTS `{$table}`");
        }

        $stmt = $db->prepare("\n            SELECT COUNT(*)\n            FROM information_schema.COLUMNS\n            WHERE TABLE_SCHEMA = DATABASE()\n              AND TABLE_NAME = 'companies'\n              AND COLUMN_NAME = 'billing_plan'\n        ");
        $stmt->execute();
        if ((int)$stmt->fetchColumn() > 0) {
            $db->exec("ALTER TABLE `companies` DROP COLUMN `billing_plan`");
        }
    } finally {
        $db->exec('SET FOREIGN_KEY_CHECKS=1');
    }
};
