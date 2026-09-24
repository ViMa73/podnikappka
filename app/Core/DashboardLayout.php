<?php

namespace Core;

class DashboardLayout
{
    public static function loadForUser(int $companyId, int $userId, array $definitions): array
    {
        $db = DB::get();

        $stmt = $db->prepare("
            SELECT widget_key, sort_order, is_enabled
            FROM user_dashboard_widgets
            WHERE company_id = ? AND user_id = ?
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([$companyId, $userId]);
        $rows = $stmt->fetchAll() ?: [];

        if (!$rows) {
            self::seedDefaults($companyId, $userId, $definitions);

            $stmt = $db->prepare("
                SELECT widget_key, sort_order, is_enabled
                FROM user_dashboard_widgets
                WHERE company_id = ? AND user_id = ?
                ORDER BY sort_order ASC, id ASC
            ");
            $stmt->execute([$companyId, $userId]);
            $rows = $stmt->fetchAll() ?: [];
        }

        $saved = [];
        foreach ($rows as $row) {
            $saved[(string)$row['widget_key']] = [
                'sort_order' => (int)$row['sort_order'],
                'is_enabled' => (int)$row['is_enabled'] === 1,
            ];
        }

        $items = [];
        foreach ($definitions as $key => $def) {
            if (empty($def['available'])) {
                continue;
            }

            $items[] = [
                'key' => $key,
                'label' => (string)$def['label'],
                'span' => (int)$def['span'],
                'view' => (string)$def['view'],
                'sort_order' => $saved[$key]['sort_order'] ?? (int)$def['default_order'],
                'is_enabled' => $saved[$key]['is_enabled'] ?? true,
            ];
        }

        usort($items, static function ($a, $b) {
            return $a['sort_order'] <=> $b['sort_order'];
        });

        return $items;
    }

    public static function saveOrder(int $companyId, int $userId, array $definitions, array $widgetKeys): void
    {
        $allowedKeys = array_keys(array_filter($definitions, static function ($def) {
            return !empty($def['available']);
        }));

        $ordered = [];
        foreach ($widgetKeys as $key) {
            $key = (string)$key;
            if (in_array($key, $allowedKeys, true) && !in_array($key, $ordered, true)) {
                $ordered[] = $key;
            }
        }

        foreach ($allowedKeys as $key) {
            if (!in_array($key, $ordered, true)) {
                $ordered[] = $key;
            }
        }

        $db = DB::get();
        $db->beginTransaction();

        try {
            foreach ($ordered as $index => $key) {
                $stmt = $db->prepare("
                    INSERT INTO user_dashboard_widgets (company_id, user_id, widget_key, sort_order, is_enabled)
                    VALUES (?, ?, ?, ?, 1)
                    ON DUPLICATE KEY UPDATE
                        sort_order = VALUES(sort_order)
                ");
                $stmt->execute([$companyId, $userId, $key, $index + 1]);
            }

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function setEnabled(int $companyId, int $userId, array $definitions, string $widgetKey, bool $enabled): void
    {
        if (
            !isset($definitions[$widgetKey]) ||
            empty($definitions[$widgetKey]['available'])
        ) {
            throw new \RuntimeException('Widget není dostupný.');
        }

        $db = DB::get();

        $stmt = $db->prepare("
            SELECT id
            FROM user_dashboard_widgets
            WHERE company_id = ? AND user_id = ? AND widget_key = ?
            LIMIT 1
        ");
        $stmt->execute([$companyId, $userId, $widgetKey]);
        $exists = $stmt->fetchColumn();

        if (!$exists) {
            self::seedDefaults($companyId, $userId, $definitions);
        }

        $stmt = $db->prepare("
            UPDATE user_dashboard_widgets
            SET is_enabled = ?
            WHERE company_id = ? AND user_id = ? AND widget_key = ?
        ");
        $stmt->execute([$enabled ? 1 : 0, $companyId, $userId, $widgetKey]);
    }

    public static function reset(int $companyId, int $userId, array $definitions): void
    {
        $db = DB::get();
        $stmt = $db->prepare("
            DELETE FROM user_dashboard_widgets
            WHERE company_id = ? AND user_id = ?
        ");
        $stmt->execute([$companyId, $userId]);

        self::seedDefaults($companyId, $userId, $definitions);
    }

    private static function seedDefaults(int $companyId, int $userId, array $definitions): void
    {
        $db = DB::get();

        $available = array_filter($definitions, static function ($def) {
            return !empty($def['available']);
        });

        uasort($available, static function ($a, $b) {
            return ((int)$a['default_order']) <=> ((int)$b['default_order']);
        });

        $order = 1;
        foreach ($available as $key => $def) {
            $stmt = $db->prepare("
                INSERT INTO user_dashboard_widgets (company_id, user_id, widget_key, sort_order, is_enabled)
                VALUES (?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE
                    sort_order = VALUES(sort_order),
                    is_enabled = 1
            ");
            $stmt->execute([$companyId, $userId, $key, $order]);
            $order++;
        }
    }
}
