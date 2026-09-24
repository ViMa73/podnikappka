<?php

namespace Core;

class AppRelease
{
    public static function current(): array
    {
        $file = self::basePath('config/version.php');

        if (!is_file($file)) {
            return [
                'version' => '0.0',
                'released_at' => null,
                'name' => null,
            ];
        }

        $data = require $file;

        return is_array($data) ? $data : [
            'version' => '0.0',
            'released_at' => null,
            'name' => null,
        ];
    }

    public static function all(): array
    {
        $file = self::basePath('config/releases.php');

        if (!is_file($file)) {
            return [];
        }

        $data = require $file;

        if (!is_array($data)) {
            return [];
        }

        usort($data, function (array $a, array $b) {
            return version_compare($b['version'] ?? '0.0', $a['version'] ?? '0.0');
        });

        return $data;
    }

    private static function basePath(string $path = ''): string
    {
        return rtrim(dirname(__DIR__, 2), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }
}
