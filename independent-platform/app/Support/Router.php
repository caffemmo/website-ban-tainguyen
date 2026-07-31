<?php
declare(strict_types=1);

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', trim($path, '/'));
        $this->routes[] = [$method, '#^/' . trim((string) $pattern, '/') . '/?$#', $handler];
    }

    public function dispatch(string $method, string $path): mixed
    {
        foreach ($this->routes as [$routeMethod, $pattern, $handler]) {
            if ($method !== $routeMethod || !preg_match($pattern, $path, $matches)) {
                continue;
            }
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return $handler($params);
        }
        http_response_code(404);
        if (is_api_request()) {
            json_response(['ok' => false, 'error' => 'Not found'], 404);
        }
        return view('errors/404', ['title' => 'Không tìm thấy trang']);
    }
}
