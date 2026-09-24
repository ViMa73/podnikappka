<?php
namespace Middleware;

use Core\Auth;

class AuthMiddleware
{
    public function handle()
    {
        if (!Auth::check()) {
            header("Location: /");
            exit;
        }
    }
}