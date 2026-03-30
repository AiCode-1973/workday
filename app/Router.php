<?php
/**
 * Router simples baseado em URL
 */
class Router {
    private array $routes = [];

    public function get(string $path, array $handler): void {
        $this->routes['GET'][$path] = $handler;
    }
    public function post(string $path, array $handler): void {
        $this->routes['POST'][$path] = $handler;
    }
    public function put(string $path, array $handler): void {
        $this->routes['PUT'][$path] = $handler;
    }
    public function delete(string $path, array $handler): void {
        $this->routes['DELETE'][$path] = $handler;
    }

    public function dispatch(string $uri, string $method): void {
        $uri = trim($uri, '/');

        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', trim($pattern, '/'));
            if (preg_match('#^' . $regex . '$#', $uri, $matches)) {
                [$controller, $action] = $handler;
                // filtra apenas named groups
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $ctrl = new $controller();
                $ctrl->$action(...array_values($params));
                return;
            }
        }

        http_response_code(404);
        require __DIR__ . '/../views/errors/404.php';
    }
}
