<?php

require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../services/authService.php';

class AuthController extends Controller
{
    // Ensure session is started before accessing session variables
    private function ensureSessionStarted()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Logs the user out, destroys the session and then send the user to login page
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

    // Login page
    public function login()
    {
        $this->ensureSessionStarted();

        $error = '';
        $email = '';

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            // Stop the user from inputting nothing
            if ($email === '' || $password === '') {
                $error = 'Vul email en wachtwoord in.';
            } else {
                // If the above is false proceed with getting an database connection and verify login
                $database = new Database();
                $connection = $database->getConnection();
                $user = verifyLogin($connection, $email, $password);

                if ($user) {
                    $_SESSION['user_id'] = (int) $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    // Determine if the user is an normal or admin role and then send him to the correct page
                    $targetRoute = $user['role'] === 'admin' ? 'admin' : 'home';
                    header('Location: ' . $this->getBasePath() . '/index.php?route=' . $targetRoute);
                    exit;
                }

                // If the function(veryifyLogin) fails, hsow this message
                $error = 'Onjuiste inloggegevens.';
            }
        }

        // Render out the login page with the following params
        $this->render('auth/login', [
            'basePath' => $this->getBasePath(),
            'error' => $error,
            'email' => $email,
        ]);
    }

    // Register page
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

            // Stops the user from inputting nothing
            if ($username === '' || $email === '' || $password === '' || $confirm === '') {
                $error = 'Vul alle velden in.';
            // Stops the user from inputting an non valid role
            } elseif (!in_array($role, $allowedRoles, true)) {
                $error = 'Ongeldige rol geselecteerd.';
            // Stops the user from inputting an not valid email
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Ongeldig emailadres.';
            // Stops the user from inputting and password that does not match the first one
            } elseif ($password !== $confirm) {
                $error = 'Wachtwoorden komen niet overeen.';
            } else {
                // Create a new instance from database and get an connection
                $database = new Database();
                $connection = $database->getConnection();

                // Stops the user from inputting an emailadress that already exists
                if (emailExists($connection, $email)) {
                    $error = 'Dit emailadres bestaat al.';
                // Stops an user from inputting an username that already exists
                } elseif (usernameExists($connection, $username)) {
                    $error = 'Deze gebruikersnaam is al in gebruik.';
                } else {
                    // If the above pass run this function below with params
                    $createdUserId = createUser($connection, $username, $email, $password, $role);

                    // Show an error if the createdUserId is null
                    if ($createdUserId === null) {
                        $error = 'Er ging iets mis bij het aanmaken van het account.';
                    } else {
                        // If the above is false proceed creating an account by storing the params in an session
                        $_SESSION['user_id'] = $createdUserId;
                        $_SESSION['email'] = $email;
                        $_SESSION['username'] = $username;
                        $_SESSION['role'] = $role;

                        // Determine if the user is an normal or admin role and then send him to the correct page
                        $targetRoute = $role === 'admin' ? 'admin' : 'home';
                        header('Location: ' . $this->getBasePath() . '/index.php?route=' . $targetRoute);
                        exit;
                    }
                }
            }
        }

        // Render out the register page with the following params
        $this->render('auth/register', [
            'basePath' => $this->getBasePath(),
            'error' => $error,
            'success' => $success,
            'email' => $email,
            'username' => $username,
            'role' => $role,
        ]);
    }
}
