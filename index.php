<?php
session_start();

require_once __DIR__ . '/config/config.php';

// Open-source edice je vždy self-hosted. Za nainstalovanou ji považujeme pouze
// tehdy, když existuje instalační zámek A současně jsou načtené DB údaje.
// Tím zabráníme pádu na Undefined constant DB_HOST při chybějícím local.php.
$installed = is_file(__DIR__ . '/storage/installed.lock')
    && defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS');

if (!$installed) {
    require __DIR__ . '/install/installer.php';
    exit;
}

spl_autoload_register(function ($class) {
    $base = __DIR__ . '/app/';
    $path = $base . str_replace('\\', '/', $class) . '.php';

    if (file_exists($path)) {
        require_once $path;
        return;
    }

    $fallback = $base . 'Controllers/' . basename(str_replace('\\', '/', $class)) . '.php';
    if (file_exists($fallback)) {
        require_once $fallback;
    }
});

\Core\ErrorHandler::register();
require_once __DIR__ . '/routes/web.php';

use Core\Router;
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
