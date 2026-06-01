<?php
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../app/controllers/viewController.php';
require_once __DIR__ . '/../app/controllers/authController.php';
require_once __DIR__ . '/../app/controllers/commentController.php';
require_once __DIR__ . '/../app/controllers/reactionController.php';

// Create a new router instance
$router = new Router();

// Define every route and its corresponding controller action

// Get routes for rendering views
$router->get('/', [viewController::class, 'index']);
$router->get('/home', [viewController::class, 'index']);
$router->get('/video', [viewController::class, 'video']);
$router->get('/user', [viewController::class, 'user']);
$router->get('/admin', [viewController::class, 'admin']);
$router->get('/subscriptions', [viewController::class, 'subscriptions']);
$router->get('/library', [viewController::class, 'library']);
$router->get('/history', [viewController::class, 'history']);

// Get routes for authentication
$router->get('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'register']);
$router->get('/logout', [AuthController::class, 'logout']);

// Post routes
$router->post('/login', [AuthController::class, 'login']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/manage-video', [viewController::class, 'manageVideo']);
$router->post('/manage-comment', [commentController::class, 'manage']);
$router->post('/react-video', [reactionController::class, 'video']);
$router->post('/react-comment', [reactionController::class, 'comment']);

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