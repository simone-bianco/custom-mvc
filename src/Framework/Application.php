<?php

declare(strict_types=1);

namespace Framework;

use Exception;
use Exceptions\PageNotFoundException;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

class Application extends Container
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        parent::__construct();

        $this->basePath = $basePath;

        static::setInstance($this);

        $this->bind(Application::class, fn() => $this);
        $this->bind(Container::class, fn () => $this);

        $this->singleton(Router::class, fn () => new Router());
    }

    protected function getPath(): string
    {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    protected function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @throws PageNotFoundException
     * @throws Exception
     */
    public function run(): void
    {
        /** @var Router $router */
        $router = $this->make(Router::class);

        $path = $this->getPath();
        $method = $this->getMethod();
        $match = $router->match($path, $method);

        if (empty($match)) {
            throw new PageNotFoundException("No route matched for path [$path] with method [$method]");
        }

        $handler = $match['handler'] ?? null;
        $routeParams = $match['params'] ?? [];
        if (is_callable($handler)) {
            echo call_user_func_array($handler, $routeParams);
        } elseif (is_array($handler) && count($handler) === 2) {
            $controllerClass = $handler[0];
            $methodName = $handler[1];

            $methodArgs = $this->resolveMethodDependencies($controllerClass, $methodName, $routeParams);
            $controller = $this->make($controllerClass);

            echo $controller->{$methodName}(...$methodArgs);
        } else {
            throw new Exception("Handler not valid for route [$path] with method [$method]");
        }
    }

    /**
     * @throws Exception
     */
    protected function resolveMethodDependencies(string $class, string $method, array $routeParams): array
    {
        try {
            $reflection = new ReflectionMethod($class, $method);
        } catch (Throwable $exception) {
            throw new Exception("Class [$class] is not reflectable");
        }

        $dependencies = [];
        $params = $reflection->getParameters();
        foreach ($params as $param) {
            $paramName = $param->getName();
            if (array_key_exists($paramName, $routeParams)) {
                $dependencies[] = $routeParams[$paramName];
                continue;
            }

            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
                continue;
            }

            if ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();
                continue;
            }

            throw new Exception("Impossibile risolvere il parametro \${$paramName} nel metodo {$class}::{$method}");
        }

        return $dependencies;
    }

    public function basePath(): string
    {
        return $this->basePath;
    }
}