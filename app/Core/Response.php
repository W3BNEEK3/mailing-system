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
    /**
 * Append an HX-Trigger header to fire a named HTMX event on the client.
 *
 * The event name maps to a document.addEventListener() listener in app.js.
 * Multiple calls chain together in a single JSON header value.
 *
 * Usage — trigger a toast:
 *   return $this->partial('compose/_form')
 *       ->htmxTrigger('showToast', ['type' => 'success', 'message' => 'Email sent.']);
 *
 * Usage — trigger multiple events:
 *   return Response::html('')
 *       ->htmxTrigger('showToast', ['type' => 'success', 'message' => 'Saved.'])
 *       ->htmxTrigger('refreshRecipients');
 *
 * @param string $eventName  The JS event name (e.g. 'showToast')
 * @param mixed  $data       Optional data passed to the event detail
 */
public function htmxTrigger(string $eventName, mixed $data = null): static
{
    // Read the current HX-Trigger header value (may already have events)
    $existing = $this->headers['HX-Trigger'] ?? null;

    $events = $existing ? json_decode($existing, true) : [];

    if ($data !== null) {
        $events[$eventName] = $data;
    } else {
        $events[$eventName] = true;
    }

    $this->headers['HX-Trigger'] = json_encode($events);
    return $this;
}

/**
 * Set the HX-Redirect header, causing HTMX to do a client-side redirect.
 *
 * Use this instead of a regular redirect when the current request was
 * made by HTMX — a normal Location redirect header won't work because
 * HTMX intercepts the response.
 *
 * Usage:
 *   return Response::html('')->htmxRedirect('/compose');
 */
public function htmxRedirect(string $url): static
{
    $this->headers['HX-Redirect'] = $url;
    return $this;
}
}
