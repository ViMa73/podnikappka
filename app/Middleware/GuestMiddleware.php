<?php
namespace Middleware;

class GuestMiddleware
{
    public function handle()
    {
        if (!empty($_SESSION['user_id'])) {
            header("Location: /dashboard");
            exit;
        }
    }
}
