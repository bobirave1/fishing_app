<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private string $basePath;
    private ?Container $container = null;

    public function __construct(string $basePath = '', ?Container $container = null)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->container = $container;
    }

    public function get(string $path, array|callable $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, array|callable $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function any(string $path, array|callable $handler): self
    {
        $this->addRoute('GET', $path, $handler);
        $this->addRoute('POST', $path, $handler);
        return $this;
    }

    public function addRoute(string $method, string $path, array|callable $handler): self
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
        ];
        return $this;
    }

    /**
     * Attempt to dispatch the current request.
     * Returns true if a route matched, false otherwise.
     */
    public function dispatch(string $method, string $uri): bool
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Strip base path
        if ($this->basePath && str_starts_with($path, $this->basePath)) {
            $path = substr($path, strlen($this->basePath)) ?: '/';
        }

        // Normalize: remove trailing slash (except root)
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $regex = $this->compilePattern($route['path']);
            if (preg_match($regex, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->callHandler($route['handler'], $params);
                return true;
            }
        }

        return false;
    }

    private function compilePattern(string $pattern): string
    {
        // Convert {param} to named capture groups
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }

    private function callHandler(array|callable $handler, array $params): void
    {
        if (is_array($handler) && count($handler) === 2 && is_string($handler[0])) {
            $controller = $this->container
                ? new $handler[0]($this->container)
                : new $handler[0]();
            $method = $handler[1];
            $controller->$method(...array_values($params));
        } else {
            call_user_func_array($handler, array_values($params));
        }
    }
}
