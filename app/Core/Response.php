<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Response
 *
 * Builds and sends HTTP responses. Uses a fluent interface so you can chain calls:
 *
 *   return Response::html('<h1>Hello</h1>');
 *   return Response::redirect('/login');
 *   return Response::html($content)->withStatus(404);
 *   return Response::html($content)->htmxTrigger('showToast', ['type' => 'success', 'message' => 'Saved!']);
 */
class Response
{
    private int    $statusCode = 200;
    private array  $headers    = [];
    private string $body       = '';

    // ─── Static factory methods ───────────────────────────────────────────

    /**
     * Create an HTML response.
     */
    public static function html(string $content, int $status = 200): static
    {
        $response = new static();
        $response->statusCode = $status;
        $response->body       = $content;
        $response->headers['Content-Type'] = 'text/html; charset=UTF-8';
        return $response;
    }

    /**
     * Create a JSON response.
     * Automatically encodes the data and sets the Content-Type header.
     */
    public static function json(mixed $data, int $status = 200): static
    {
        $response = new static();
        $response->statusCode = $status;
        $response->body       = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response->headers['Content-Type'] = 'application/json; charset=UTF-8';
        return $response;
    }

    /**
     * Create a redirect response.
     * Default status 302 = temporary redirect. Use 301 for permanent.
     */
    public static function redirect(string $url, int $status = 302): static
    {
        $response = new static();
        $response->statusCode      = $status;
        $response->headers['Location'] = $url;
        return $response;
    }

    /**
     * Redirect back to the previous page (using the Referer header).
     * Falls back to '/' if there is no Referer.
     */
    public static function back(): static
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return static::redirect($referer);
    }

    // ─── Fluent modifiers ─────────────────────────────────────────────────

    /**
     * Override or add an HTTP response header.
     * Returns $this so calls can be chained.
     */
    public function withHeader(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Override the HTTP status code.
     */
    public function withStatus(int $status): static
    {
        $this->statusCode = $status;
        return $this;
    }

    /**
     * Add an HTMX HX-Trigger header.
     *
     * HTMX reads this header and fires a JavaScript event on the page.
     * We use it to trigger toast notifications from the server:
     *
     *   ->htmxTrigger('showToast', ['type' => 'success', 'message' => 'Saved!'])
     *
     * The JS in app.js listens for the 'showToast' event and renders the toast.
     */
    public function htmxTrigger(string $eventName, mixed $data = null): static
    {
        $trigger = $data !== null
            ? json_encode([$eventName => $data])
            : json_encode([$eventName => true]);

        $this->headers['HX-Trigger'] = $trigger;
        return $this;
    }

    /**
     * Tell HTMX to perform a client-side redirect.
     *
     * Different from a normal redirect: the browser doesn't reload the page,
     * instead HTMX navigates using its own history API.
     * Use this in HTMX responses when you want smooth navigation after a form submit.
     */
    public function htmxRedirect(string $url): static
    {
        $this->headers['HX-Redirect'] = $url;
        return $this;
    }

    // ─── Sending ─────────────────────────────────────────────────────────

    /**
     * Send the response to the browser and stop execution.
     *
     * This method never returns — it always calls exit after sending.
     * That's why the return type is 'never'.
     */
    public function send(): never
    {
        // Set the HTTP status code
        http_response_code($this->statusCode);

        // Send all headers
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        // Send the body
        echo $this->body;

        exit;
    }

    /**
     * Stream a file to the browser.
     *
     * Used by StorageController to serve uploaded logos and templates
     * that live outside the public web root.
     *
     * This method never returns.
     */
    public function stream(string $filepath, string $mimeType): never
    {
        if (!file_exists($filepath)) {
            http_response_code(404);
            echo 'File not found.';
            exit;
        }

        http_response_code(200);
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: public, max-age=3600');

        // Read and output the file in chunks to handle large files
        // without loading the entire file into memory at once
        $handle = fopen($filepath, 'rb');
        if ($handle !== false) {
            while (!feof($handle)) {
                echo fread($handle, 8192); // 8KB chunks
            }
            fclose($handle);
        }

        exit;
    }
}
