<?php

class Router
{
    private $routes = [];

    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function dispatch($method, $path)
    {
        $normalizedMethod = strtoupper($method);
        $normalizedPath = $this->normalizePath($path);

        if (!isset($this->routes[$normalizedMethod][$normalizedPath])) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Route not found.']);
            return;
        }

        $handler = $this->routes[$normalizedMethod][$normalizedPath];

        if (is_array($handler) && count($handler) === 2) {
            $controllerClass = $handler[0];
            $action = $handler[1];
            $controller = new $controllerClass();
            $controller->$action();
            return;
        }

        call_user_func($handler);
    }

    private function addRoute($method, $path, $handler)
    {
        $normalizedMethod = strtoupper($method);
        $normalizedPath = $this->normalizePath($path);

        if (!isset($this->routes[$normalizedMethod])) {
            $this->routes[$normalizedMethod] = [];
        }

        $this->routes[$normalizedMethod][$normalizedPath] = $handler;
    }

    private function normalizePath($path)
    {
        $trimmedPath = trim($path);

        if ($trimmedPath === '' || $trimmedPath === '/') {
            return '/';
        }

        return '/' . trim($trimmedPath, '/');
    }
}