<?php

require_once __DIR__ . '/../autoload.php';

use App\Classes\Auth\Authentication;

// Initialize authentication
$auth = new Authentication();

// Simple routing
$route = $_GET['route'] ?? 'login';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login':
                $result = $auth->login($_POST['username'], $_POST['password']);
                if (isset($result['success'])) {
                    header('Location: /?route=dashboard');
                    exit;
                }
                $error = $result['error'] ?? 'Login failed';
                break;
            
            case 'register':
                $result = $auth->register($_POST['username'], $_POST['password']);
                if (isset($result['success'])) {
                    header('Location: /?route=dashboard');
                    exit;
                }
                $error = $result['error'] ?? 'Registration failed';
                break;
            
            case 'logout':
                $auth->logout();
                header('Location: /?route=login');
                exit;
        }
    }
}

// Check if user is logged in
if ($auth->isLoggedIn() && $route !== 'logout') {
    $route = 'dashboard';
}

// Include the appropriate view
switch ($route) {
    case 'register':
        require __DIR__ . '/../App/Views/auth/register.php';
        break;
    case 'dashboard':
        require __DIR__ . '/../App/Views/dashboard/index.php';
        break;
    default:
        require __DIR__ . '/../App/Views/auth/login.php';
        break;
}
