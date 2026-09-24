<?php
namespace Core;
class Edition
{
    public static function current(): string { return 'self_hosted'; }
    public static function isSelfHosted(): bool { return true; }
    public static function isInstalled(): bool
    {
        $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 2);
        return is_file($root . '/storage/installed.lock') && defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER');
    }
}
