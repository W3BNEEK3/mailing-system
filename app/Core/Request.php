<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Request
 *
 * Wraps the incoming HTTP request.
 * Created once via Request::capture() and passed through the application.
 */
class Request
{
    private string $method;
    private string $uri;
    private array  $getParams;
    private array  $postParams;
    private array  $files;
    private array  $server;
    private array  $headers;

    private function __construct()
    {
        $this->method = strtoupper(
            $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET'
        );

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->uri = strtok($uri, '?') ?: '/';

        if (!str_starts_with($this->uri, '/')) {
            $this->uri = '/' . $this->uri;
        }

        $this->getParams  = $_GET    ?? [];
        $this->postParams = $_POST   ?? [];
        $this->files      = $_FILES  ?? [];
        $this->server     = $_SERVER ?? [];
        $this->headers    = $this->parseHeaders();
    }

    public static function capture(): static
    {
        return new static();
    }

    public function method(): string { return $this->method; }
    public function isGet(): bool    { return $this->method === 'GET'; }
    public function isPost(): bool   { return $this->method === 'POST'; }
    public function isPut(): bool    { return $this->method === 'PUT'; }
    public function isPatch(): bool  { return $this->method === 'PATCH'; }
    public function isDelete(): bool { return $this->method === 'DELETE'; }

    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Read a value from $_GET. If $key is null, returns all GET data.
     */
    public function get(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) return $this->getParams;
        return $this->getParams[$key] ?? $default;
    }

    /**
     * Read a value from $_POST. If $key is null, returns all POST data.
     */
    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) return $this->postParams;
        return $this->postParams[$key] ?? $default;
    }

    /**
     * Read a value from POST first, then GET. If $key is null, returns all data.
     */
    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) return $this->all();
        return $this->postParams[$key] ?? $this->getParams[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->getParams, $this->postParams);
    }

    public function only(array $keys): array
    {
        $all    = $this->all();
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $all[$key] ?? null;
        }
        return $result;
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $file;
    }

    public function header(string $key): ?string
    {
        $normalised = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $this->headers[$normalised] ?? null;
    }

    public function isHtmx(): bool
    {
        return $this->header('HX-Request') === 'true';
    }

    public function expectsJson(): bool
    {
        $accept = $this->header('Accept') ?? '';
        return str_contains($accept, 'application/json');
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function bearerToken(): ?string
    {
        $auth = $this->server['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }

    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[$key] = $value;
            }
        }
        return $headers;
    }
}