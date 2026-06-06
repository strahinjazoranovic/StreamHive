<?php

class Controller
{
    // Render a view file and pass data to it.
    protected function render($view, $data = [])
    {
        $viewPath = __DIR__ . '/../../views/' . $view . '.php';

        // Return a 500 error if the requested view does not exist.
        if (!file_exists($viewPath)) {
            http_response_code(500);
            echo 'View not found.';
            return;
        }

        // Make array keys available as variables in the view.
        extract($data, EXTR_SKIP);

        require $viewPath;
    }

    // Send a JSON response with the specified HTTP status code.
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    // Get the application's base path relative to the web root.
    protected function getBasePath()
    {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

        // Return an empty string when running from the web root.
        if ($scriptDir === '/' || $scriptDir === '.') {
            return '';
        }

        return rtrim($scriptDir, '/');
    }
}