<?php

require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../services/authService.php';

class AuthController extends Controller
{
    private function ensureSessionStarted()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function logout()
    {
        $this->ensureSessionStarted();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        header('Location: ' . $this->getBasePath() . '/index.php?route=login');
        exit;
    }
    public function login()
    {
        $this->ensureSessionStarted();

        $error = '';
        $email = '';

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($email === '' || $password === '') {
                $error = 'Vul email en wachtwoord in.';
            } else {
                $database = new Database();
                $connection = $database->getConnection();
                $user = verifyLogin($connection, $email, $password);

                if ($user) {
                    $_SESSION['user_id'] = (int) $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    $targetRoute = $user['role'] === 'admin' ? 'admin' : 'home';
                    header('Location: ' . $this->getBasePath() . '/index.php?route=' . $targetRoute);
                    exit;
                }

                $error = 'Onjuiste inloggegevens.';
            }
        }

        $this->render('login/login', [
            'basePath' => $this->getBasePath(),
            'error' => $error,
            'email' => $email,
        ]);
    }

    public function register()
    {
        $this->ensureSessionStarted();

        $error = '';
        $success = '';
        $email = '';
        $username = '';
        $role = 'user';

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            $role = trim($_POST['role'] ?? 'user');
            $allowedRoles = ['user', 'admin'];

            if ($username === '' || $email === '' || $password === '' || $confirm === '') {
                $error = 'Vul alle velden in.';
            } elseif (!in_array($role, $allowedRoles, true)) {
                $error = 'Ongeldige rol geselecteerd.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Ongeldig emailadres.';
            } elseif ($password !== $confirm) {
                $error = 'Wachtwoorden komen niet overeen.';
            } else {
                $database = new Database();
                $connection = $database->getConnection();

                if (emailExists($connection, $email)) {
                    $error = 'Dit emailadres bestaat al.';
                } elseif (usernameExists($connection, $username)) {
                    $error = 'Deze gebruikersnaam is al in gebruik.';
                } else {
                    $createdUserId = createUser($connection, $username, $email, $password, $role);

                    if ($createdUserId === null) {
                        $error = 'Er ging iets mis bij het aanmaken van het account.';
                    } else {
                        $_SESSION['user_id'] = $createdUserId;
                        $_SESSION['email'] = $email;
                        $_SESSION['username'] = $username;
                        $_SESSION['role'] = $role;

                        $targetRoute = $role === 'admin' ? 'admin' : 'home';
                        header('Location: ' . $this->getBasePath() . '/index.php?route=' . $targetRoute);
                        exit;
                    }
                }
            }
        }

        $this->render('login/register', [
            'basePath' => $this->getBasePath(),
            'error' => $error,
            'success' => $success,
            'email' => $email,
            'username' => $username,
            'role' => $role,
        ]);
    }
}
