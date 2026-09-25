<?php

namespace UCrazy\AplikasiPencatatBarangCrud\App;

class Router
{
    private static array $routes = [];

    public static function add(string $method, string $path, string $controller, string $function, array $middlewares = []): void
    {
        self::$routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'function' => $function,
            'middleware' => $middlewares
        ];
    }

    public static function run(): void
    {
        $path = '/';
        if (isset($_SERVER['PATH_INFO'])) {
            $path = $_SERVER['PATH_INFO'];
        } else {
            $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
            $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
            if ($script !== '/' && str_starts_with($uri, $script)) {
                $uri = substr($uri, strlen($script)) ?: '/';
            }
            $path = $uri ?: '/';
        }

        $method = $_SERVER['REQUEST_METHOD'];

        foreach (self::$routes as $route) {
            $pattern = "#^" . $route['path'] . "$#";
            if (preg_match($pattern, $path, $variables) && $method == $route['method']) {
                foreach ($route['middleware'] as $middleware) {
                    $instance = new $middleware;
                    $instance->before();
                }

                $function = $route['function'];
                $controller = new $route['controller'];

                array_shift($variables);
                call_user_func_array([$controller, $function], $variables);
                return;
            }
        }

        http_response_code(404);
        if (str_starts_with($path, '/api/')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Endpoint tidak ditemukan: ' . $path]);
            return;
        }
        echo 'CONTROLLER NOT FOUND: ' . htmlspecialchars($path);
    }
}
