<?php

namespace App\Classes\Auth;

use App\Classes\Database;
use App\Interfaces\AuthenticationInterface;
use App\Interfaces\UserRepositoryInterface;

class Authentication implements AuthenticationInterface {
    private UserRepositoryInterface $database;

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->database = new Database();
    }

    public function register(string $username, string $password): array {
        if (empty($username) || empty($password)) {
            return ['error' => 'Username and password are required.'];
        }
        if (strlen($password) < 6) {
             return ['error' => 'Password must be at least 6 characters long.'];
        }

        // Check if user exists
        $existingUser = $this->database->findByUsername($username);
        if ($existingUser instanceof User) {
            return ['error' => 'Username already exists'];
        }

        // Create new User instance
        $user = new User([
            'username' => $username
        ]);

        // Hash password and create user
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $userId = $this->database->create($user, $hash);

            if ($userId === false) {
                return ['error' => 'Registration failed due to a database error.'];
            }

            // Update user with the new ID
            $user->id = $userId;
            
            // Log in the user
            $this->performLogin($user);

            return ['success' => true, 'user' => $user];
        } catch (\Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['error' => 'An internal error occurred during registration.'];
        }
    }

    public function login(string $username, string $password): array {
        if (empty($username) || empty($password)) {
            return ['error' => 'Username or password is empty'];
        }

        // Get user
        $user = $this->database->findByUsername($username);
        if (!$user instanceof User) {
            return ['error' => 'User not found'];
        }

        // Get password hash and verify
        $credentials = $this->database->getUserCredentials($username);
        if (!$credentials || !password_verify($password, $credentials['password_hash'])) {
            return ['error' => 'Invalid password'];
        }

        // Perform login
        $this->performLogin($user);
        return ['success' => true, 'user' => $user];
    }

    /**
     * Handles the session creation part of the login process.
     */
    private function performLogin(User $user): void {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['username'] = $user->username;
    }

    public function logout(): array {
        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        return ['success' => true];
    }
    
    public function isLoggedIn(): bool {
        return isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
    }
    
    public function getCurrentUser(): ?User {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return $this->database->findById((int)$_SESSION['user_id']);
    }
}
