<?php
namespace App;

class Router {
    private array $routes = [];

    public function get(string $path, array|callable $handler, array $middlewares = []): void {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array|callable $handler, array $middlewares = []): void {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function any(string $path, array|callable $handler, array $middlewares = []): void {
        $this->addRoute('ANY', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, array|callable $handler, array $middlewares): void {
        $pattern = '#^' . preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', rtrim($path, '/')) . '/?$#';
        if ($path === '/' || $path === '') {
            $pattern = '#^/?$#';
        }
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(Request $request): void {
        $method = $request->getMethod();
        $path = $request->getPath();

        foreach ($this->routes as $route) {
            if ($route['method'] !== 'ANY' && $route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                // Execute middlewares
                foreach ($route['middlewares'] as $middleware) {
                    if (is_string($middleware)) {
                        // Check if middleware has parameters e.g. "RoleMiddleware:admin"
                        $mParts = explode(':', $middleware, 2);
                        $mClass = "App\\Middleware\\" . $mParts[0];
                        $mArg = $mParts[1] ?? null;

                        if (class_exists($mClass)) {
                            $instance = new $mClass();
                            $instance->handle($request, $mArg);
                        }
                    } elseif (is_callable($middleware)) {
                        $middleware($request);
                    }
                }

                // Execute handler
                $handler = $route['handler'];
                if (is_callable($handler)) {
                    call_user_func($handler, $request, $params);
                    return;
                }

                if (is_array($handler) && count($handler) === 2) {
                    [$class, $action] = $handler;
                    $controller = new $class();
                    call_user_func([$controller, $action], $request, $params);
                    return;
                }
            }
        }

        // 404 Not Found
        if ($request->isAjax()) {
            Response::error('Endpoint not found', 404);
        } else {
            Response::view('auth/404', ['title' => 'Page Not Found'], 'auth');
        }
    }
}
