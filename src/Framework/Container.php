<?php

declare(strict_types=1);

namespace Framework;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionNamedType;
use Throwable;

class Container
{
    protected static ?Container $instance = null;

    protected array $bindings = [];
    protected array $instances = [];

    protected function __construct() {}

    public static function setInstance(?Container $container = null): ?self
    {
        return self::$instance = $container;
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function bind(string $abstract, Closure|string|null $concrete = null, bool $shared = false): void
    {
        $concrete ??= $abstract;

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared
        ];
    }

    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     * @throws Exception
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract]['concrete'] ?? $abstract;
        if ($concrete instanceof Closure) {
            $object = $concrete($this, $parameters);
        } else {
            $object = $this->build($concrete, $parameters);
        }

        if (isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared']) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * @throws Exception
     */
    protected function build(string $concrete, array $parameters): object
    {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (Throwable $throwable) {
            throw new Exception("Target class [$concrete] does not exist", 0, $throwable);
        }

        if (!$reflector->isInstantiable()) {
            throw new Exception("Target class [$concrete] is not instantiable");
        }

        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $parameterName = $parameter->getName();

            if (array_key_exists($parameterName, $parameters)) {
                $dependencies[] = $parameters[$parameterName];
                continue;
            }

            $parameterType = $parameter->getType();
            if (!($parameterType instanceof ReflectionNamedType) || $parameterType->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new Exception("Unresolvable dependency resolving [$parameterName] in class [$concrete]");
            }

            $dependencies[] = $this->make($parameterType->getName());
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
