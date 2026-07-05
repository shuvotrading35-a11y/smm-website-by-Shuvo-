<?php

declare(strict_types=1);

namespace SMMPanel\Core;

use SMMPanel\Middleware\AuthMiddleware;
use SMMPanel\Middleware\AdminMiddleware;
use SMMPanel\Middleware\CsrfMiddleware;
use SMMPanel\Middleware\RateLimitMiddleware;

/**
 * Router — lightweight regex-based HTTP router.
 *
 * Supports GET, POST, middleware stacks, and parameter capture.
 */
final class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:callable|array, middleware:string[]}> */
    private array $routes = [];

    private array $groupMiddleware = [];
    private string $groupPrefix    = '';

    // ── Route Registration ────────────────────────────────────

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function any(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('GET|POST', $path, $handler, $middleware);
    }

    /**
     * Group routes under a shared prefix and middleware stack.
     */
    public function group(array $options, callable $callback): void
    {
        $previousPrefix     = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix     .= ($options['prefix'] ?? '');
        $this->groupMiddleware  = array_merge(
            $this->groupMiddleware,
            $options['middleware'] ?? []
        );

        $callback($this);

        $this->groupPrefix     = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    private function addRoute(string $method, string $path, callable|array $handler, array $middleware): void
    {
        $pattern = $this->groupPrefix . $path;

        $this->routes[] = [
            'method'     => $method,
            'pattern'    => $this->compilePattern($pattern),
            'handler'    => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    // ── Dispatch ──────────────────────────────────────────────

    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri    = $this->normaliseUri($_SERVER['REQUEST_URI'] ?? '/');

        // Method spoofing via hidden _method field (for HTML forms)
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        foreach ($this->routes as $route) {
            $allowedMethods = explode('|', $route['method']);

            if (!in_array($method, $allowedMethods, true) && !in_array('GET|POST', $allowedMethods, true)) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Strip numeric keys from matches
                $params = array_filter(
                    $matches,
                    fn($k) => !is_int($k),
                    ARRAY_FILTER_USE_KEY
                );

                $this->runMiddleware($route['middleware'], function () use ($route, $params) {
                    $this->callHandler($route['handler'], $params);
                });

                return;
            }
        }

        $this->handle404();
    }

    // ── Middleware Runner ─────────────────────────────────────

    private function runMiddleware(array $middleware, callable $final): void
    {
        $pipeline = $final;

        foreach (array_reverse($middleware) as $mw) {
            $next     = $pipeline;
            $pipeline = function () use ($mw, $next) {
                $instance = $this->resolveMiddleware($mw);
                $instance->handle($next);
            };
        }

        $pipeline();
    }

    private function resolveMiddleware(string $name): object
    {
        return match ($name) {
            'auth'       => new AuthMiddleware(),
            'admin'      => new AdminMiddleware(),
            'csrf'       => new CsrfMiddleware(),
            'rate:login' => new RateLimitMiddleware('login', 5, 60),
            'rate:reg'   => new RateLimitMiddleware('register', 3, 60),
            'rate:api'   => new RateLimitMiddleware('api', 60, 60),
            default      => throw new \InvalidArgumentException("Unknown middleware: {$name}"),
        };
    }

    // ── Handler Caller ────────────────────────────────────────

    private function callHandler(callable|array $handler, array $params): void
    {
        if (is_callable($handler)) {
            $handler($params);
            return;
        }

        [$controllerClass, $method] = $handler;

        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller [{$controllerClass}] not found.");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $method)) {
            throw new \RuntimeException("Method [{$method}] not found on [{$controllerClass}].");
        }

        $controller->$method($params);
    }

    // ── Pattern Compilation ───────────────────────────────────

    /**
     * Convert /users/:id into a named-capture regex.
     */
    private function compilePattern(string $path): string
    {
        $pattern = preg_replace('/\/:([a-zA-Z_][a-zA-Z0-9_]*)/', '/(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '/?$#';

        return $pattern;
    }

    // ── Helpers ───────────────────────────────────────────────

    private function normaliseUri(string $uri): string
    {
        $uri = strtok($uri, '?') ?: '/';
        $uri = '/' . trim($uri, '/');

        return $uri === '' ? '/' : $uri;
    }

    private function handle404(): void
    {
        http_response_code(404);
        require_once __DIR__ . '/../Views/errors/404.php';
        exit;
    }
}
