<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Request
 *
 * Wraps the incoming HTTP request.
 * Created once via Request::capture() and passed through the application.
 *
 * Usage:
 *   $request = Request::capture();
 *   $request->method();            // 'GET', 'POST', 'PUT', 'DELETE'
 *   $request->uri();               // '/recipients/5'
 *   $request->post('email');       // $_POST['email'] or null
 *   $request->get('page');         // $_GET['page'] or null
 *   $request->input('name');       // checks POST then GET
 *   $request->file('avatar');      // $_FILES['avatar'] or null
 *   $request->isHtmx();            // true if sent by HTMX
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
        // Read the HTTP method. HTML forms can only send GET and POST,
        // so we support a hidden _method field for PUT/PATCH/DELETE.
        $this->method = strtoupper(
            $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET'
        );

        // Strip the query string from the URI
        // '/recipients?page=2' becomes '/recipients'
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->uri = strtok($uri, '?') ?: '/';

        // Make sure the URI always starts with /
        if (!str_starts_with($this->uri, '/')) {
            $this->uri = '/' . $this->uri;
        }

        $this->getParams  = $_GET    ?? [];
        $this->postParams = $_POST   ?? [];
        $this->files      = $_FILES  ?? [];
        $this->server     = $_SERVER ?? [];

        // Parse headers from $_SERVER
        $this->headers = $this->parseHeaders();
    }

    /**
     * Create a Request from the current PHP superglobals.
     * This is the entry point — called once in App::run().
     */
    public static function capture(): static
    {
        return new static();
    }

    // ─── HTTP Method ──────────────────────────────────────────────────────

    public function method(): string
    {
        return $this->method;
    }

    public function isGet(): bool    { return $this->method === 'GET'; }
    public function isPost(): bool   { return $this->method === 'POST'; }
    public function isPut(): bool    { return $this->method === 'PUT'; }
    public function isPatch(): bool  { return $this->method === 'PATCH'; }
    public function isDelete(): bool { return $this->method === 'DELETE'; }

    // ─── URI ──────────────────────────────────────────────────────────────

    public function uri(): string
    {
        return $this->uri;
    }

    // ─── Input data ───────────────────────────────────────────────────────

    /**
     * Read a value from $_GET (query string).
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getParams[$key] ?? $default;
    }

    /**
     * Read a value from $_POST (form body).
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->postParams[$key] ?? $default;
    }

    /**
     * Read a value from POST first, then GET.
     * Use this when you don't care where the value comes from.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->postParams[$key] ?? $this->getParams[$key] ?? $default;
    }

    /**
     * Get all POST and GET values merged together.
     * POST values win if the same key exists in both.
     */
    public function all(): array
    {
        return array_merge($this->getParams, $this->postParams);
    }

    /**
     * Get only specific keys from the request input.
     * Useful for passing only safe fields to the database.
     */
    public function only(array $keys): array
    {
        $all    = $this->all();
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $all[$key] ?? null;
        }
        return $result;
    }

    /**
     * Get an uploaded file's data from $_FILES.
     * Returns null if no file was uploaded with that field name.
     */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        // A file with error code UPLOAD_ERR_NO_FILE means nothing was uploaded
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $file;
    }

    // ─── Headers ──────────────────────────────────────────────────────────

    /**
     * Get an HTTP request header value.
     *
     * Pass the header name in normal format: 'Content-Type', 'X-CSRF-Token'
     * This method handles the $_SERVER naming convention internally.
     */
    public function header(string $key): ?string
    {
        // Convert 'X-CSRF-Token' to 'HTTP_X_CSRF_TOKEN' for $_SERVER lookup
        $normalised = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $this->headers[$normalised] ?? null;
    }

    /**
     * Check if this request was made by HTMX.
     * HTMX automatically sends 'HX-Request: true' with every request it makes.
     */
    public function isHtmx(): bool
    {
        return $this->header('HX-Request') === 'true';
    }

    /**
     * Check if the client wants a JSON response.
     * (Checks for 'Accept: application/json' header)
     */
    public function expectsJson(): bool
    {
        $accept = $this->header('Accept') ?? '';
        return str_contains($accept, 'application/json');
    }

    /**
     * Get the client's IP address.
     */
    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Parse a Bearer token from the Authorization header.
     * Returns null if no Bearer token is present.
     */
    public function bearerToken(): ?string
    {
        $auth = $this->server['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }

    // ─── Internal ─────────────────────────────────────────────────────────

    /**
     * Extract HTTP headers from $_SERVER.
     *
     * PHP stores headers in $_SERVER with an HTTP_ prefix and uppercase
     * names with underscores. e.g. 'Content-Type' => 'HTTP_CONTENT_TYPE'.
     * We store them in the same format for consistency.
     */
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
