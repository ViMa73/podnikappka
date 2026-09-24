<?php
namespace Core;

use PDO;

class DB
{
    private static ?PDO $instance = null;

    public static function get(): PDO
    {
        if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
            throw new \RuntimeException('Databázová konfigurace není načtena. Zkontrolujte soubor config/local.php.');
        }

        if (!self::$instance) {
            self::$instance = new PDO(
                "mysql:host=".DB_HOST.";port=".(defined('DB_PORT') ? DB_PORT : 3306).";dbname=".DB_NAME.";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        }
        return self::$instance;
    }
}