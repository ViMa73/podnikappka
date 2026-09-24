<?php

namespace Core;

use PDO;
use Throwable;

class MigrationRunner
{
    private PDO $db;
    private string $migrationsPath;

    public function __construct(?PDO $db = null, ?string $migrationsPath = null)
    {
        $this->db = $db ?: DB::get();
        $this->migrationsPath = $migrationsPath ?: $this->basePath('database/migrations');
    }

    public function ensureMigrationsTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                executed_at DATETIME NOT NULL,
                executed_by_user_id INT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'success',
                message TEXT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function getAllMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . DIRECTORY_SEPARATOR . '*.php');
        sort($files, SORT_STRING);

        return $files ?: [];
    }

    public function getExecutedMigrations(): array
    {
        $this->ensureMigrationsTable();

        $stmt = $this->db->query("
            SELECT name
            FROM migrations
            WHERE status = 'success'
            ORDER BY name ASC
        ");

        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $rows ?: [];
    }

    public function getPendingMigrations(): array
    {
        $files = $this->getAllMigrationFiles();
        $executed = $this->getExecutedMigrations();
        $executedMap = array_flip($executed);

        $pending = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');

            if (!isset($executedMap[$name])) {
                $pending[] = [
                    'name' => $name,
                    'file' => $file,
                ];
            }
        }

        return $pending;
    }

    public function getHistory(int $limit = 50): array
    {
        $this->ensureMigrationsTable();

        $stmt = $this->db->prepare("
            SELECT *
            FROM migrations
            ORDER BY executed_at DESC, id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function runPending(?int $executedByUserId = null): array
    {
        $this->ensureMigrationsTable();

        $pending = $this->getPendingMigrations();
        $results = [];

        foreach ($pending as $migration) {
            $name = $migration['name'];
            $file = $migration['file'];

            try {
                $callable = require $file;

                if (!is_callable($callable)) {
                    throw new \RuntimeException("Migrace {$name} nevrátila callable.");
                }

                $this->db->beginTransaction();

                $callable($this->db);

                $stmt = $this->db->prepare("
                    INSERT INTO migrations (name, executed_at, executed_by_user_id, status, message)
                    VALUES (?, NOW(), ?, 'success', ?)
                ");
                $stmt->execute([
                    $name,
                    $executedByUserId,
                    'OK',
                ]);

                if ($this->db->inTransaction()) {
                    $this->db->commit();
                }

                $results[] = [
                    'name' => $name,
                    'status' => 'success',
                    'message' => 'OK',
                ];
            } catch (Throwable $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }

                try {
                    $stmt = $this->db->prepare("
                        INSERT INTO migrations (name, executed_at, executed_by_user_id, status, message)
                        VALUES (?, NOW(), ?, 'failed', ?)
                    ");
                    $stmt->execute([
                        $name,
                        $executedByUserId,
                        mb_substr($e->getMessage(), 0, 5000),
                    ]);
                } catch (Throwable $ignored) {
                }

                $results[] = [
                    'name' => $name,
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];

                break;
            }
        }

        return $results;
    }

    private function basePath(string $path = ''): string
    {
        return rtrim(dirname(__DIR__, 2), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }
}
