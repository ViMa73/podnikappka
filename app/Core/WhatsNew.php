<?php

namespace Core;

use PDO;
use Throwable;

class WhatsNew
{
    private static function columnExists(PDO $db, string $table, string $column): bool
    {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM `{$table}` LIKE " . $db->quote($column));
            return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function shouldAutoOpen(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $current = AppRelease::current();
        $currentVersion = (string) ($current['version'] ?? '');

        if ($currentVersion === '') {
            return false;
        }

        $db = DB::get();

        if (!self::columnExists($db, 'users', 'last_seen_release_version')) {
            return false;
        }

        $stmt = $db->prepare("
            SELECT last_seen_release_version
            FROM users
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([Auth::user()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $lastSeen = (string) ($row['last_seen_release_version'] ?? '');

        return $lastSeen !== $currentVersion;
    }

    public static function markCurrentVersionAsSeen(): void
    {
        if (!Auth::check()) {
            return;
        }

        $current = AppRelease::current();
        $currentVersion = (string) ($current['version'] ?? '');

        if ($currentVersion === '') {
            return;
        }

        $db = DB::get();

        if (!self::columnExists($db, 'users', 'last_seen_release_version')) {
            return;
        }

        $stmt = $db->prepare("
            UPDATE users
            SET last_seen_release_version = ?
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$currentVersion, Auth::user()]);
    }
}
