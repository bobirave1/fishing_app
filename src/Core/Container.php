<?php

namespace App\Core;

/**
 * Lightweight dependency injection container.
 *
 * Supports singleton and factory bindings.
 * Services are lazily resolved on first access.
 */
class Container
{
    /** @var array<string, callable> */
    private array $factories = [];

    /** @var array<string, object> */
    private array $instances = [];

    /**
     * Register a factory that will be called once (singleton).
     */
    public function singleton(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    /**
     * Register a pre-built instance.
     */
    public function instance(string $id, object $value): void
    {
        $this->instances[$id] = $value;
    }

    /**
     * Resolve an entry by its identifier.
     *
     * @template T
     * @param class-string<T> $id
     * @return T
     * @throws \RuntimeException If the entry is not found.
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->factories[$id])) {
            $this->instances[$id] = ($this->factories[$id])($this);
            return $this->instances[$id];
        }

        throw new \RuntimeException("Container: no binding for [{$id}].");
    }

    /**
     * Check whether the container has a binding for the given identifier.
     */
    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->factories[$id]);
    }

    /**
     * Get the raw PDO connection (convenience shortcut).
     */
    public function pdo(): \PDO
    {
        return $this->get(\PDO::class);
    }
}
