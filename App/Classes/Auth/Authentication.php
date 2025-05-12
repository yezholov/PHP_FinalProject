<?php

namespace App\Classes\Auth;

use App\Classes\Database;
use App\Classes\Security\KeyManager;
use App\Interfaces\KeyManagerInterface;
use App\Interfaces\AuthenticationInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Classes\Auth\User;

class Authentication implements AuthenticationInterface {
    private UserRepositoryInterface $database;
    private $keyManager;

    public function __construct(?KeyManagerInterface $keyManager = null) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->database = new Database();
        $this->keyManager = $keyManager ?? new KeyManager();
    }

    // Register the user
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

        // Prepare data (hashing password, generating/encrypting key)
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $aesKey = $this->keyManager->generateKey();
            $encryptedKey = $this->keyManager->encryptKey($aesKey, $password);
        } catch (\Exception $e) {
            error_log("Key encryption/hashing error during registration: " . $e->getMessage());
            return ['error' => 'An internal error occurred during registration preparation.'];
        }

        // Create user and key
        $userId = $this->database->createUserWithKey($username, $hash, $encryptedKey);

        if ($userId === false) {
            return ['error' => 'Registration failed due to a database error.'];
        }

        // Store decrypted key in session and log in
        $_SESSION['aes_key'] = $aesKey;
        $this->performLogin($userId, $username);

        return ['success' => true, 'user_id' => $userId];
    }

    // Login the user
    public function login(string $username, string $password): array {
        if (empty($username) || empty($password)) {
            return ['error' => 'Username or password is empty'];
        }

        // Get user and verify password
        $user = $this->database->findByUsername($username);
        if (!$user instanceof User) {
            return ['error' => 'User not found'];
        }

        // Get credentials and verify
        $credentials = $this->database->getUserCredentials($username);
        if (!$credentials || !password_verify($password, $credentials['password_hash'])) {
            return ['error' => 'Invalid password'];
        }

        // Get and decrypt the AES key
        try {
            $encryptedKey = $this->database->getUserKey($credentials['id']);
            if (!$encryptedKey) {
                throw new \Exception('User key not found');
            }

            $decryptedKey = $this->keyManager->decryptKey($encryptedKey, $password);
            if ($decryptedKey === false) {
                throw new \Exception('Failed to decrypt user key');
            }

            // Store decrypted key in session
            $_SESSION['aes_key'] = $decryptedKey;
        } catch (\Exception $e) {
            error_log("Key decryption error during login: " . $e->getMessage());
            return ['error' => 'An error occurred during login'];
        }

        // Complete login
        $this->performLogin($credentials['id'], $username);
        return ['success' => true, 'user_id' => $credentials['id']];
    }

    // Handles the user in the session
    private function performLogin(int $userId, string $username): void {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
    }

    // Logout the user
    public function logout(): array {
        // Remove the AES key from session first
        unset($_SESSION['aes_key']);
        
        // Clear all other session data
        $_SESSION = array();

        // From the documentation: https://www.php.net/manual/en/function.session-destroy.php
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

    // Check if the user is logged in
    public function isLoggedIn(): bool {
        return isset($_SESSION['user_id']) && isset($_SESSION['aes_key']) && is_numeric($_SESSION['user_id']);
    }

    // Get the current user
    public function getCurrentUser(): ?User {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return $this->database->findById((int)$_SESSION['user_id']);
    }

    // Get the user ID from the session
    public function getUserId()
    {
        return $this->isLoggedIn() ? (int)$_SESSION['user_id'] : null;
    }

    // Get the username from the session
    public function getUsername()
    {
        return $this->isLoggedIn() ? $_SESSION['username'] : null;
    }

    // Get the decrypted AES key from session
    
    public function getAesKey(): ?string
    {
        return $_SESSION['aes_key'] ?? null;
    }

    // Change the password with re-encryption key
    public function changePassword(int $userId, string $oldPassword, string $newPassword, string $confirmPassword): array
    {
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            return ['error' => 'All password fields are required.'];
        }
        if ($newPassword !== $confirmPassword) {
            return ['error' => 'New passwords do not match.'];
        }
        if (strlen($newPassword) < 6) {
            return ['error' => 'New password must be at least 6 characters long.'];
        }

        // Get current user credentials
        $user = $this->database->findById($userId);
        if (!$user) {
            return ['error' => 'User not found.']; // Should not happen if $userId is from session
        }
        $credentials = $this->database->getUserCredentials($user->username);
        if (!$credentials || !password_verify($oldPassword, $credentials['password_hash'])) {
            return ['error' => 'Invalid current password.'];
        }

        // Fetch the currently encrypted AES key from DB
        $currentEncryptedAesKey = $this->database->getUserKey($userId);
        if (!$currentEncryptedAesKey) {
            // This is a critical error, it's should not happen
            error_log("User {$userId} has no AES key in DB during password change.");
            return ['error' => 'Could not retrieve your security key. Please contact support.'];
        }

        try {
            // Decrypt AES key with OLD password
            $plainAesKey = $this->keyManager->decryptKey($currentEncryptedAesKey, $oldPassword);
            if ($plainAesKey === false) {
                error_log("Failed to decrypt AES key for user {$userId} with OLD password.");
                return ['error' => 'Failed to verify your current security key. Check your old password or contact support.'];
            }

            // Encrypt AES key with NEW password
            $newEncryptedAesKey = $this->keyManager->encryptKey($plainAesKey, $newPassword);
            
            // Hash the NEW password
            $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);

            // Update database
            $updated = $this->database->updatePasswordAndKey($userId, $newPasswordHash, $newEncryptedAesKey);

            if ($updated) {
                $_SESSION['aes_key'] = $plainAesKey; 
                return ['success' => true];
            } else {
                return ['error' => 'Failed to update password in the database.'];
            }
        } catch (\Exception $e) {
            error_log("Error during password change for user {$userId}: " . $e->getMessage());
            return ['error' => 'An internal error occurred while changing password.'];
        }
    }
}
