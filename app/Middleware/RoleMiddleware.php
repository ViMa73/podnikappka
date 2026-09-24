<?php
namespace Middleware;

use Core\Auth;

class RoleMiddleware
{
    private array $allowed;

    public function __construct(...$roles)
    {
        $this->allowed = $roles;
    }

    public function handle()
    {
        if (!in_array(Auth::role(), $this->allowed)) {
            http_response_code(403);
            exit("403 Forbidden");
        }
    }
}