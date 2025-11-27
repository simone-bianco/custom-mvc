<?php

declare(strict_types=1);

namespace Framework;

class Router
{
    private array $routes = [];

    public function get(string $path, array|callable $handler, ?string $name = null): void
    {
        $this->add('GET', $path, $handler, $name);
    }

    public function post(string $path, array|callable $handler, ?string $name = null): void
    {
        $this->add('POST', $path, $handler, $name);
    }

    private function add(string $method, string $path, array|callable $handler, ?string $name = null): void
    {
        // Ottimizzazione: Calcoliamo la regex ORA, non ad ogni richiesta
        $regex = $this->compileRouteRegex($path);

        $routeData = [
            'path' => $path,
            'method' => strtoupper($method),
            'handler' => $handler,
            'regex' => $regex
        ];

        // Se c'è un nome, usiamo la chiave, altrimenti appendiamo
        if ($name) {
            $this->routes[$name] = $routeData;
        } else {
            $this->routes[] = $routeData;
        }
    }

    public function match(string $path, string $method): ?array
    {
        $path = rawurldecode(rtrim($path, '/')); // rtrim gestisce path con slash finale
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            // Puliamo i match numerici, teniamo solo quelli nominativi
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            return [
                'handler' => $route['handler'],
                'params' => $params
            ];
        }

        return null; // Ritorna null se non trova nulla (più pulito di false)
    }

    private function compileRouteRegex(string $routePath): string
    {
        // Gestione path vuoto o root
        if ($routePath === '/') {
            return '#^/$#iu';
        }

        $segments = array_map(function (string $segment) {
            // Caso 1: {id} -> accetta tutto tranne lo slash
            if (preg_match('#^\{([a-zA-Z0-9_]+)\}$#', $segment, $matches)) {
                return "(?<{$matches[1]}>[^/]+)";
            }

            // Caso 2: {id:\d+} -> accetta regex custom
            if (preg_match('#^\{([a-zA-Z0-9_]+):(.+)\}$#', $segment, $matches)) {
                return "(?<{$matches[1]}>{$matches[2]})";
            }

            return preg_quote($segment, '#'); // Importante: escape dei caratteri speciali
        }, explode('/', ltrim($routePath, '/')));

        return '#^/' . implode('/', $segments) . '$#iu';
    }
}