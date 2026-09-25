<?php

return function (\PDO $db): void {
    $stmt = $db->query("SHOW COLUMNS FROM service_places LIKE 'lock_on_holiday'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE service_places ADD COLUMN lock_on_holiday TINYINT(1) NOT NULL DEFAULT 0 AFTER notes_enabled");
    }
};
