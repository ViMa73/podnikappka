<?php
namespace Middleware;

use Core\Auth;

class AdminMiddleware
{
    public function handle(): void
    {
        if (!Auth::check()) {
            header('Location: /');
            exit;
        }

        if (!Auth::isSuperAdmin()) {
            http_response_code(403);
            echo 'Přístup odepřen.';
            exit;
        }
    }
}
