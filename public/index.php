<?php
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../app/controllers/homeController.php';
require_once __DIR__ . '/../app/controllers/authController.php';

$router = new Router();

// Define every route and its corresponding controller action
$router->get('/', [HomeController::class, 'index']);
$router->get('/home', [HomeController::class, 'index']);
$router->get('/admin', [HomeController::class, 'admin']);
$router->get('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'register']);

$requestedPath = '/';
$route = $_GET['route'] ?? '';

if ($route !== '') {
    $requestedPath = '/' . trim($route, '/');
} else {
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

    if (!is_string($uriPath)) {
        $uriPath = '/';
    }

    if ($scriptDir !== '/' && $scriptDir !== '.' && strpos($uriPath, $scriptDir) === 0) {
        $uriPath = substr($uriPath, strlen($scriptDir));
    }

    if ($uriPath === '' || $uriPath === false || $uriPath === '/index.php') {
        $requestedPath = '/';
    } else {
        $requestedPath = '/' . trim($uriPath, '/');
    }
}

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $requestedPath);
