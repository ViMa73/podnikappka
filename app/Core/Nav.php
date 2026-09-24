<?php
namespace Core;

class Nav
{
    /**
     * Vrátí true, pokud aktuální URL odpovídá:
     * - přesně (default)
     * - nebo prefixově (např. /users je aktivní i na /users/edit/5)
     */
    public static function isActive(string $path, bool $prefix = true): bool
    {
        $current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        // sjednocení trailing slash
        $current = rtrim($current, '/') ?: '/';
        $path = rtrim($path, '/') ?: '/';

        if ($prefix) {
            // /users aktivní pro /users i /users/...
            return $current === $path || str_starts_with($current, $path . '/');
        }

        return $current === $path;
    }

    public static function linkClass(string $path, bool $prefix = true): string
    {
        $base = "flex items-center gap-2 p-2 rounded-lg transition inactive-menu";
        $active = "font-bold active-menu";

        return self::isActive($path, $prefix) ? "$base $active" : $base;
    }
}
