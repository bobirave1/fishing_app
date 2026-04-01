<?php

namespace App\Core;

/**
 * Base controller with shared helper methods.
 */
abstract class Controller
{
    protected \PDO $pdo;
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->pdo = $container->pdo();
    }

    /**
     * Resolve a service from the container.
     *
     * @template T
     * @param class-string<T> $id
     * @return T
     */
    protected function service(string $id): object
    {
        return $this->container->get($id);
    }

    protected function logger(): Logger
    {
        return $this->container->get(Logger::class);
    }

    protected function requireAuth(): int
    {
        return Middleware::auth();
    }

    protected function requireCsrf(): void
    {
        Middleware::csrf();
    }

    /**
     * Render a template inside the main layout.
     */
    protected function render(string $template, array $data = []): void
    {
        Response::view($template, $data);
    }

    /**
     * Return a JSON success response.
     */
    protected function jsonOk(array $data = []): never
    {
        Response::jsonOk($data);
    }

    /**
     * Return a JSON error response.
     */
    protected function jsonError(string $message, int $status = 400): never
    {
        Response::jsonError($message, $status);
    }

    /**
     * Redirect to a URL.
     */
    protected function redirect(string $url): never
    {
        Response::redirect($url);
    }
}
