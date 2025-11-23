<?php

declare(strict_types=1);

namespace Framework;

use Exception;

class Router
{
    private array $routes = [];

    /**
     * @param string $name
     * @param string $path
     * @param array $params
     * @return void
     * @throws Exception
     */
    public function add(string $name, string $path, array $params = []): void
    {
        if (empty($name)) {
            throw new Exception('Route name is required');
        }

        $this->routes[$name] = [
            'path' => $path,
            'params' => $params
        ];
    }

    public function match(string $path, string $method): array|bool
    {
        $path = urldecode(trim($path));

        foreach ($this->routes as $route) {
            $pattern = $this->getExpression($route['path']);

            if (!preg_match($pattern, $path, $matches)) {
                continue;
            }

            $matches = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            $params = array_merge($matches, $route['params']);
            if (array_key_exists('method', $params) && strtolower($method) !== strtolower($params['method'])) {
                continue;
            }

            return $params;
        }

        return false;
    }

    private function getExpression(string $routePath): string
    {
        $segments = array_map(function (string $segment) {
            if (preg_match('#^\{([a-z][a-z0-9]*)\}$#', $segment, $matches)) {
                return "(?<{$matches[1]}>[^/]*)";
            }

            if (preg_match('#^\{([a-z][a-z0-9]*):(.+)\}$#', $segment, $matches)) {
                return "(?<{$matches[1]}>$matches[2])";
            }

            return $segment;
        }, explode('/', trim($routePath)));

        return '#' . implode('/', $segments) . '#iu';
    }
}
