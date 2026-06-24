<?php

declare(strict_types=1);

namespace App\Core;

/**
 * App (Service Container)
 *
 * Manages class instances and how they're created.
 *
 * Two binding types:
 *
 *   Singleton: Created once, then the same instance is returned every time.
 *     $app->singleton(Logger::class, fn() => new Logger('/path/to/logs'));
 *     $app->make(Logger::class); // same Logger instance every call
 *
 *   Bind: A fresh instance is created every time make() is called.
 *     $app->bind(SomeService::class, fn() => new SomeService());
 *     $app->make(SomeService::class); // new SomeService every call
 *
 * Global access:
 *   App::getInstance() // from anywhere in the codebase
 */
class App
{
    /**
     * The single global instance of App.
     * Set via setInstance() and retrieved via getInstance().
     */
    private static ?App $instance = null;

    /**
     * Singleton factories — lazy, called only once per abstract.
     * Format: ['ClassName' => callable]
     */
    private array $singletons = [];

    /**
     * Resolved singleton instances (cached after first make() call).
     * Format: ['ClassName' => object|value]
     */
    private array $instances = [];

    /**
     * Bind factories — called fresh every make().
     * Format: ['ClassName' => callable]
     */
    private array $bindings = [];

    // ─── Registration ─────────────────────────────────────────────────────

    /**
     * Register a singleton binding.
     * The factory callable is only called once; the result is cached.
     *
     * Usage:
     *   $app->singleton(Logger::class, fn() => new Logger(storage_path('logs')));
     */
    public function singleton(string $abstract, callable $factory): void
    {
        $this->singletons[$abstract] = $factory;
    }

    /**
     * Register a regular binding (new instance every time).
     *
     * Usage:
     *   $app->bind(SomeService::class, fn() => new SomeService());
     */
    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /**
     * Register a pre-made instance directly (no factory needed).
     * The same instance is returned every time.
     *
     * Usage:
     *   $app->instance(Request::class, $request);
     */
    public function instance(string $abstract, mixed $value): void
    {
        $this->instances[$abstract] = $value;
    }

    // ─── Resolution ───────────────────────────────────────────────────────

    /**
     * Resolve a binding and return the instance.
     *
     * Resolution order:
     *   1. Pre-made instances (registered with instance())
     *   2. Singleton bindings (cached after first call)
     *   3. Regular bindings (fresh call each time)
     *   4. RuntimeException if nothing is registered
     */
    public function make(string $abstract): mixed
    {
        // 1. Check pre-made instances first
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // 2. Check singleton bindings
        if (isset($this->singletons[$abstract])) {
            // Create the instance and cache it
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = ($this->singletons[$abstract])($this);
            }
            return $this->instances[$abstract];
        }

        // 3. Check regular bindings (creates a fresh instance each time)
        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        // 4. Nothing registered
        throw new \RuntimeException(
            "No binding found for [{$abstract}]. " .
            "Register it in bootstrap/app.php using singleton(), bind(), or instance()."
        );
    }

    // ─── Global access ────────────────────────────────────────────────────

    /**
     * Store the global App instance.
     * Called once in bootstrap/app.php.
     */
    public static function setInstance(self $app): void
    {
        static::$instance = $app;
    }

    /**
     * Retrieve the global App instance from anywhere in the codebase.
     * Used by helper functions like config(), session(), logger().
     */
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            throw new \RuntimeException(
                'App instance not set. Make sure bootstrap/app.php has been loaded.'
            );
        }
        return static::$instance;
    }

    // ─── Request Lifecycle ────────────────────────────────────────────────

    /**
     * Run the application.
     *
     * This is the main entry point called from public/index.php.
     * It handles the full request lifecycle:
     *   1. Load .env
     *   2. Start session
     *   3. Capture the HTTP request
     *   4. Dispatch to the correct controller
     *   5. Send the response
     *
     * This method never returns (it calls exit via Response::send()).
     */
    public function run(): never
    {
        // 1. Load environment variables from .env
        EnvLoader::load(BASE_PATH . '/.env');

        // 2. Set the application timezone
        $timezone = $_ENV['TIMEZONE'] ?? 'UTC';
        date_default_timezone_set($timezone);

        // 3. Start the session
        $this->make(Session::class)->start();

        // 4. Capture the current HTTP request
        $request = Request::capture();

        // 5. Dispatch the request through the router
        $router   = $this->make(Router::class);
        $response = $router->dispatch($request);

        // 6. Send the response to the browser
        $response->send();
    }
}
