<?php
namespace Core;

class Router
{
    private array $routes = [];

    public function get($uri, $action, $middleware = [])
    {
        $this->add('GET', $uri, $action, $middleware);
    }

    public function post($uri, $action, $middleware = [])
    {
        $this->add('POST', $uri, $action, $middleware);
    }

    private function add($method, $uri, $action, $middleware)
    {
        $this->routes[] = compact('method', 'uri', 'action', 'middleware');
    }

    public function dispatch($uri, $method)
    {
        $uriPath = parse_url($uri, PHP_URL_PATH);

        foreach ($this->routes as $route) {
            // podpora parametrů {token}
            $pattern = preg_replace('/\{[\w]+\}/', '([\w\-]+)', $route['uri']);
            if (preg_match('#^' . $pattern . '$#', $uriPath, $matches) && $method === $route['method']) {
                array_shift($matches);

                foreach ($route['middleware'] as $mw) {
                    (new $mw)->handle();
                }

                [$class, $function] = $route['action'];
                (new $class)->$function(...$matches);
                return;
            }
        }

        http_response_code(404);

        if (\Core\Auth::check()) {
            $view = 'errors/404';
            $title = 'Stránka nenalezena';
            require __DIR__ . '/../Views/layout.php';
        } else {
            require __DIR__ . '/../Views/errors/404_guest.php';
        }

        return;
    }
}