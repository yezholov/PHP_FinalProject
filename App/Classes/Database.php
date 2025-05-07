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
} 