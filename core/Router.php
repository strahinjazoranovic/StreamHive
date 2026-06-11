<?php

// Router class for registering and dispatching routes.
class Router
{
    // Stores all registered routes.
    private $routes = [];

    // Register a GET route.
    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }

    // Register a POST route.
    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }

    // Match and execute the route handler.
    public function dispatch($method, $path)
    {
        $normalizedMethod = strtoupper($method);
        $normalizedPath = $this->normalizePath($path);

        // Return 404 if the route does not exist.
        if (!isset($this->routes[$normalizedMethod][$normalizedPath])) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Route not found.']);
            return;
        }

        // Get the registered route handler.
        $handler = $this->routes[$normalizedMethod][$normalizedPath];

        // Instantiate and execute controller action.
        if (is_array($handler) && count($handler) === 2) {
            $controllerClass = $handler[0];
            $action = $handler[1];
            $controller = new $controllerClass();
            $controller->$action();
            return;
        }

        // Execute a callable handler.
        call_user_func($handler);
    }

    // Add a route to the route collection.
    private function addRoute($method, $path, $handler)
    {
        $normalizedMethod = strtoupper($method);
        $normalizedPath = $this->normalizePath($path);

        // Create method group if it does not exist.
        if (!isset($this->routes[$normalizedMethod])) {
            $this->routes[$normalizedMethod] = [];
        }

        // Store the route handler.
        $this->routes[$normalizedMethod][$normalizedPath] = $handler;
    }

    // Normalize route paths for consistent matching.
    private function normalizePath($path)
    {
        $trimmedPath = trim($path);

        // Treat empty paths as root.
        if ($trimmedPath === '' || $trimmedPath === '/') {
            return '/';
        }

        // Ensure a leading slash and remove extra slashes.
        return '/' . trim($trimmedPath, '/');
    }
}