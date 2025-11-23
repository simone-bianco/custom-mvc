<?php

declare(strict_types=1);

namespace Framework;

use Exception;

class Router
{
    private array $routes = [];

    /**
     * @param string $method
     * @param string $name
     * @param string $path
     * @param array $params
     * @return void
     * @throws Exception
     */
    private function add(string $method, string $name, string $path, array $params): void
    {
        if (empty($name)) {
            throw new Exception('Route name is required');
        }

        $class = array_key_first($params);
        if (!$class || !class_exists($class)) {
            throw new Exception("Class '$class' does not exist");
        }

        $call = $params[$class];
        if (!$call || !method_exists($class, $call)) {
            throw new Exception("Method '$call' for class '$class' does not exist");
        }

        $this->routes[$name] = [
            'path' => $path,
            'params' => [
                'method' => $method,
                'class' => $class,
                'call' => $call
            ]
        ];
    }

    public function get(string $name, string $path, array $params): void
    {
        $this->add('get', $name, $path, $params);
    }

    public function match(string $path, string $method): array|bool
    {
        $path = urldecode(trim($path));

        foreach ($this->routes as $route) {
            if ($route['params']['method'] !== $method) {
                continue;
            }

            $pattern = $this->getExpression($route['path']);

            if (!preg_match($pattern, $path, $matches)) {
                continue;
            }

            $matches = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            return array_merge($matches, $route['params']);
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
