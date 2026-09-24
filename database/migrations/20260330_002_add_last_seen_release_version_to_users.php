<?php

return function (\PDO $db): void {
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'last_seen_release_version'");
    $exists = $stmt->fetch();

    if (!$exists) {
        $db->exec("
            ALTER TABLE users
            ADD COLUMN last_seen_release_version VARCHAR(50) NULL AFTER is_super_admin
        ");
    }
};
