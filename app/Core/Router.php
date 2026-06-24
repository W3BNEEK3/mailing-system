<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\NotFoundException;

/**
 * Router
 *
 * Matches incoming HTTP requests to registered routes and dispatches them
 * to the appropriate controller method, after running any assigned middleware.
 *
 * Route registration:
 *   $router->get('/recipients',         [RecipientController::class, 'index']);
 *   $router->post('/recipients',        [RecipientController::class, 'store']);
 *   $router->get('/recipients/{id}',    [RecipientController::class, 'show']);
 *   $router->delete('/recipients/{id}', [RecipientController::class, 'destroy']);
 *
 * Groups (apply middleware to multiple routes at once):
 *   $router->group(['middleware' => ['auth']], function($r) {
 *       $r->get('/compose', [ComposeController::class, 'index']);
 *   });
 */
class Router
{
    /**
     * All registered routes.
     *
     * Each entry looks like:
     * [
     *   'method'     => 'GET',
     *   'uri'        => '/recipients/{id}',
     *   'handler'    => [RecipientController::class, 'show'],
     *   'middleware' => ['auth'],
     * ]
     */
    private array $routes = [];

    /**
     * Middleware currently active for the open group (if any).
     */
    private array $currentGroupMiddleware = [];

    // ─── Route Registration ───────────────────────────────────────────────

    public function get(string $uri, array $handler): void
    {
        $this->addRoute('GET', $uri, $handler);
    }

    public function post(string $uri, array $handler): void
    {
        $this->addRoute('POST', $uri, $handler);
    }

    public function put(string $uri, array $handler): void
    {
        $this->addRoute('PUT', $uri, $handler);
    }

    public function patch(string $uri, array $handler): void
    {
        $this->addRoute('PATCH', $uri, $handler);
    }

    public function delete(string $uri, array $handler): void
    {
        $this->addRoute('DELETE', $uri, $handler);
    }

    /**
     * Group routes together and apply shared options (like middleware) to all of them.
     *
     * Usage:
     *   $router->group(['middleware' => ['auth', 'csrf']], function($r) {
     *       $r->get('/dashboard', [DashboardController::class, 'index']);
     *   });
     */
    public function group(array $options, callable $callback): void
    {
        // Save the current middleware, then add the group's middleware
        $previousMiddleware = $this->currentGroupMiddleware;
        $this->currentGroupMiddleware = array_merge(
            $previousMiddleware,
            $options['middleware'] ?? []
        );

        // Run the callback — any routes registered inside will inherit the group middleware
        $callback($this);

        // Restore middleware to what it was before the group
        $this->currentGroupMiddleware = $previousMiddleware;
    }

    /**
     * Register a route in the route table.
     */
    private function addRoute(string $method, string $uri, array $handler): void
    {
        $this->routes[] = [   
            'method'     => $method,
            'uri'        => $uri,
            'handler'    => $handler,
            'middleware' => $this->currentGroupMiddleware,
        ];

    }

    // ─── Dispatching ──────────────────────────────────────────────────────

    /**
     * Find the matching route for the current request and execute it.
     *
     * Steps:
     * 1. Loop through registered routes looking for a method + URI match.
     * 2. Extract any {param} values from the URI.
     * 3. Run the middleware stack.
     * 4. Instantiate the controller and call the method.
     * 5. Return the Response.
     */
    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            // First check: does the HTTP method match?
            if ($route['method'] !== $request->method()) {
                continue;
            }

            // Second check: does the URI match? Extract any {param} values.
            $params = $this->matchUri($route['uri'], $request->uri());

            if ($params === null) {
                // This route doesn't match the current URI — try the next one
                continue;
            }

            // We found a matching route — run it
            return $this->runRoute($route, $request, $params);
        }

        // No route matched
        throw new NotFoundException("No route found for [{$request->method()}] {$request->uri()}");
    }

    /**
     * Attempt to match a route URI pattern against the actual request URI.
     *
     * Converts '{param}' placeholders into named regex capture groups.
     *
     * Examples:
     *   Pattern '/recipients/{id}' matches '/recipients/5' => ['id' => '5']
     *   Pattern '/compose'         matches '/compose'       => []
     *   Pattern '/compose'         does NOT match '/compose/new' => null
     *
     * Returns:
     *   array  - matched params (may be empty [])
     *   null   - no match
     */
    private function matchUri(string $pattern, string $uri): ?array
    {
        // Convert route pattern to regex:
        // '/recipients/{id}' becomes '/recipients/(?P<id>[^/]+)'
        $regex = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $pattern);

        // Add anchors so the full URI must match (not just a prefix)
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $uri, $matches)) {
            return null; // No match
        }

        // Filter out the numeric keys from preg_match, keep only named params
        $params = array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);

        return $params;
    }

    /**
     * Run a matched route: execute middleware, then call the controller method.
     */
    private function runRoute(array $route, Request $request, array $params): Response
    {
        [$controllerClass, $method] = $route['handler'];

        // Verify the controller class exists
        if (!class_exists($controllerClass)) {
            throw new \RuntimeException(
                "Controller class [{$controllerClass}] does not exist."
            );
        }

        $controller = new $controllerClass();

        // Verify the method exists on the controller
        if (!method_exists($controller, $method)) {
            throw new \RuntimeException(
                "Method [{$method}] does not exist on [{$controllerClass}]."
            );
        }

        // Build the middleware pipeline.
        // The middleware list is ['auth', 'csrf'] etc.
        // We need to resolve those names to actual middleware class instances.
        $middlewareStack = $this->resolveMiddleware($route['middleware']);

        // The final "next" callable at the center of the pipeline
        // is the actual controller method call.
        $core = function (Request $request) use ($controller, $method, $params): Response {
            // Call the controller method.
            // We pass $request first, then any route params (like $id).
            // PHP named arguments allow the router params to match method param names.
            return $controller->$method($request, ...$params);
        };

        // Wrap the middleware around the core, from last to first,
        // so the first middleware in the list runs first.
        $pipeline = array_reduce(
            array_reverse($middlewareStack),
            fn($next, $middleware) => fn(Request $req) => $middleware->handle($req, $next),
            $core
        );

        return $pipeline($request);
    }

    /**
     * Convert middleware name strings to middleware class instances.
     *
     * Middleware name => class mapping:
     *   'auth'  => AuthMiddleware
     *   'guest' => GuestMiddleware
     *   'csrf'  => CsrfMiddleware
     */
    private function resolveMiddleware(array $names): array
    {
        $map = [
            'auth'  => \App\Middlewares\AuthMiddleware::class,
            'guest' => \App\Middlewares\GuestMiddleware::class,
            'csrf'  => \App\Middlewares\CsrfMiddleware::class,
        ];

        return array_map(function (string $name) use ($map) {
            if (!isset($map[$name])) {
                throw new \RuntimeException(
                    "Unknown middleware [{$name}]. Register it in Router::resolveMiddleware()."
                );
            }
            return new $map[$name]();
        }, $names);
    }
}
