<?php
namespace Core;

class ErrorHandler
{
    public static function register()
    {
        set_exception_handler([self::class, 'handle']);
        set_error_handler([self::class, 'handleError']);
    }

    public static function handle($e)
    {
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo "<h1>Internal Server Error</h1>";
        echo "<pre>".$e->getMessage()."</pre>";
    }

    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
}