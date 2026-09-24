<?php

// PodnikAppka Open Source je vždy self-hosted edice.
defined('APP_EDITION') || define('APP_EDITION', 'self_hosted');
define('APP_ROOT', dirname(__DIR__));

// Lokální konfiguraci vytvoří instalační průvodce.
$localConfig = __DIR__ . '/local.php';
if (is_file($localConfig)) {
    require $localConfig;
}

// Výchozí hodnoty pro volitelné SMTP. Lze je později upravit v config/local.php.
defined('SMTP_HOST') || define('SMTP_HOST', '');
defined('SMTP_USER') || define('SMTP_USER', '');
defined('SMTP_PASS') || define('SMTP_PASS', '');
defined('SMTP_PORT') || define('SMTP_PORT', 587);
defined('SMTP_ENCRYPTION') || define('SMTP_ENCRYPTION', 'tls');

defined('BASE_URL') || define('BASE_URL', '/');
