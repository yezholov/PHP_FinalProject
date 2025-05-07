<?php

namespace App\Classes;

// Import the User entity
use App\Classes\Auth\User;
use App\Interfaces\DatabaseInterface;
use App\Interfaces\UserRepositoryInterface;

class Database implements DatabaseInterface, UserRepositoryInterface {
    private $conn;

    public function __construct() {
        try {
            require __DIR__ . '/../config.php';

            if (!isset($address) || !isset($database) || !isset($user) || !isset($pass)) {
                throw new \Exception("Database configuration variables are not properly set");
            }

            $this->conn = new \mysqli($address, $user, $pass, $database);
            
            if ($this->conn->connect_error) {
                throw new \Exception("Connection failed: " . $this->conn->connect_error);
            }
            // Set charset to utf8mb4 for better compatibility
            if (!$this->conn->set_charset("utf8mb4")) {
                 error_log("Error loading character set utf8mb4: " . $this->conn->error);
            }
        } catch (\Exception $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function beginTransaction(): bool {
        return $this->conn->begin_transaction();
    }

    public function commit(): bool {
        return $this->conn->commit();
    }

    public function rollback(): bool {
        return $this->conn->rollback();
    }

    /**
     * Creates a new user in the database.
     *
     * @param User $user User object with username set
     * @param string $passwordHash Hashed password
     * @return int|false Returns the new user ID on success, false on failure
     */
    public function create(User $user, string $passwordHash): int|false {
        $sql = "INSERT INTO users (username, password_hash) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("DB prepare error (createUser): " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("ss", $user->username, $passwordHash);
        if (!$stmt->execute()) {
            error_log("Error creating user: " . $stmt->error);
            $stmt->close();
            return false;
        }

        $userId = $this->conn->insert_id;
        $stmt->close();
        return $userId;
    }

    /**
     * Finds a user by username and returns a User object.
     *
     * @param string $username
     * @return User|null Returns User object or null if not found
     */
    public function findByUsername(string $username): ?User {
        $sql = "SELECT id, username, created_at, updated_at FROM users WHERE username = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("DB prepare error (findByUsername): " . $this->conn->error);
            return null;
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData = $result->fetch_assoc();
        $stmt->close();
        
        return $userData ? new User($userData) : null;
    }

     /**
      * Finds a user by ID and returns a User object.
      *
      * @param int $id
      * @return User|null Returns User object or null if not found
      */
     public function findById(int $id): ?User {
         $sql = "SELECT id, username, created_at, updated_at FROM users WHERE id = ? LIMIT 1";
         $stmt = $this->conn->prepare($sql);
         if (!$stmt) {
             error_log("DB prepare error (findById): " . $this->conn->error);
             return null;
         }
         $stmt->bind_param("i", $id);
         $stmt->execute();
         $result = $stmt->get_result();
         $userData = $result->fetch_assoc(); 
         $stmt->close();

         return $userData ? new User($userData) : null;
     }

    /**
     * Retrieves user credentials for login verification.
     *
     * @param string $username
     * @return array|null ['id' => int, 'password_hash' => string] or null if not found
     */
    public function getUserCredentials(string $username): ?array {
        $sql = "SELECT id, password_hash FROM users WHERE username = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("DB prepare error (getUserCredentials): " . $this->conn->error);
            return null;
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $credentials = $result->fetch_assoc();
        $stmt->close();
        return $credentials;
    }

    /**
     * Retrieves the encrypted AES key for a user
     *
     * @param int $userId
     * @return string|null Returns the encrypted key or null if not found
     */
    public function getUserKey(int $userId): ?string {
        $sql = "SELECT aes_key_encrypted FROM user_keys WHERE user_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("DB prepare error (getUserKey): " . $this->conn->error);
            return null;
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? $row['aes_key_encrypted'] : null;
    }

    /**
     * Creates a new user and their associated encrypted key in a transaction
     *
     * @param string $username
     * @param string $passwordHash
     * @param string $encryptedKey
     * @return int|false Returns new user ID or false on failure
     */
    public function createUserWithKey(string $username, string $passwordHash, string $encryptedKey): int|false {
        $this->conn->begin_transaction();
        try {
            // Create user
            $sql = "INSERT INTO users (username, password_hash) VALUES (?, ?)";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new \Exception("Failed to prepare user creation statement");
            }
            
            $stmt->bind_param("ss", $username, $passwordHash);
            if (!$stmt->execute()) {
                throw new \Exception("Failed to create user");
            }
            
            $userId = $this->conn->insert_id;
            $stmt->close();

            // Create key
            $sql = "INSERT INTO user_keys (user_id, aes_key_encrypted) VALUES (?, ?)";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new \Exception("Failed to prepare key creation statement");
            }
            
            $stmt->bind_param("is", $userId, $encryptedKey);
            if (!$stmt->execute()) {
                throw new \Exception("Failed to store key");
            }
            
            $stmt->close();
            $this->conn->commit();
            
            return $userId;
        } catch (\Exception $e) {
            $this->conn->rollback();
            error_log("Transaction failed: " . $e->getMessage());
            return false;
        }
    }

    public function updatePasswordAndKey(int $userId, string $newPasswordHash, string $newEncryptedKey): bool {
        $this->conn->begin_transaction();
        try {
            // Update password hash in users table
            $sqlUser = "UPDATE users SET password_hash = ? WHERE id = ?";
            $stmtUser = $this->conn->prepare($sqlUser);
            if (!$stmtUser) {
                throw new \Exception("Failed to prepare user password update statement: " . $this->conn->error);
            }
            $stmtUser->bind_param("si", $newPasswordHash, $userId);
            if (!$stmtUser->execute()) {
                throw new \Exception("Failed to update user password: " . $stmtUser->error);
            }
            $stmtUser->close();

            // Update encrypted AES key in user_keys table
            $sqlKey = "UPDATE user_keys SET aes_key_encrypted = ? WHERE user_id = ?";
            $stmtKey = $this->conn->prepare($sqlKey);
            if (!$stmtKey) {
                throw new \Exception("Failed to prepare user key update statement: " . $this->conn->error);
            }
            $stmtKey->bind_param("si", $newEncryptedKey, $userId);
            if (!$stmtKey->execute()) {
                throw new \Exception("Failed to update user key: " . $stmtKey->error);
            }
            $stmtKey->close();

            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            $this->conn->rollback();
            error_log("Password and key update transaction failed for user {$userId}: " . $e->getMessage());
            return false;
        }
    }
} 