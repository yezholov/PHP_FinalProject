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

            case 'change-password':
                if (!$auth->isLoggedIn()) {
                    header('Location: /?route=login');
                    exit;
                }
                $userId = $auth->getUserId(); // Assuming getUserId() exists
                if ($userId === null) { // Should not happen if isLoggedIn is true
                     $_SESSION['change_password_error'] = 'User not identified.';
                     header('Location: /?route=change-password');
                     exit;
                }
                $result = $auth->changePassword(
                    $userId,
                    $_POST['old_password'],
                    $_POST['new_password'],
                    $_POST['confirm_password']
                );
                if (isset($result['success'])) {
                    $_SESSION['change_password_success'] = 'Password changed successfully.';
                } else {
                    $_SESSION['change_password_error'] = $result['error'] ?? 'Failed to change password.';
                }
                header('Location: /?route=change-password');
                exit;
        }
    }
}

// Check if user is logged in
if ($auth->isLoggedIn() && !in_array($route, ['logout', 'change-password'])) {
    if ($route !== 'dashboard') { // Allow access to change-password even if default is dashboard
       // $route = 'dashboard'; // Keep this commented or adjust logic if change-password should redirect to dashboard if not explicitly called
    }
} elseif (!in_array($route, ['login', 'register'])) {
    // If not logged in and not trying to access login/register, redirect to login
    // This also protects change-password from non-logged-in users if they try to access via GET
    if ($route === 'change-password' && !$auth->isLoggedIn()) {
         header('Location: /?route=login');
         exit;
    }
}

// Include the appropriate view
switch ($route) {
    case 'register':
        require __DIR__ . '/../App/Views/auth/register.php';
        break;
    case 'dashboard':
        require __DIR__ . '/../App/Views/dashboard/index.php';
        break;
    case 'change-password':
        require __DIR__ . '/../App/Views/auth/change_password.php';
        break;
    default:
        require __DIR__ . '/../App/Views/auth/login.php';
        break;
}
