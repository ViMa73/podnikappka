<?php
namespace Core;

class CSRF
{
    public static function generate()
    {
        if (!isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function check($token)
    {
        return isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
    }

    public static function field()
    {
        $token = self::generate();
        return '<input type="hidden" name="_csrf" value="'.$token.'">';
    }

    public static function validate(): void
    {
        if (!self::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = "Neplatný formulář (CSRF).";
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit;
        }
    }
}
