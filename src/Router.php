<?php

namespace App;

use App\Helpers\Response;

class Router
{
    private array $routes = [];

    public function get(string $path, mixed $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, mixed $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, mixed $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, mixed $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, mixed $handler): void
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<\1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        $this->routes[] = ['method' => $method, 'pattern' => $pattern, 'handler' => $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            $params = array_filter($matches, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);

            $handler = $route['handler'];
            if (is_array($handler) && count($handler) === 2) {
                [$class, $methodName] = $handler;
                $instance = new $class();
                $instance->$methodName($params);
            } else {
                $handler($params);
            }
            return;
        }

        Response::error(404, 'NOT_FOUND', 'Endpoint não encontrado.');
    }
}
