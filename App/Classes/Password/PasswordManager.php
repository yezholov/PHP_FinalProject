<?php

namespace App\Classes\Password;

use App\Classes\Database;
use App\Classes\Security\KeyManager;
use App\Classes\Security\PasswordGenerator;
use App\Interfaces\PasswordManagerInterface;
use App\Services\KeyManagerServiceProvider;

class PasswordManager implements PasswordManagerInterface {
    private Database $database;
    private KeyManager $keyManager;

    public function __construct(
        Database $database,
        ?KeyManager $keyManager = null,
        ?PasswordGenerator $passwordGenerator = null
    ) {
        $this->database = $database;
        $this->keyManager = $keyManager ?? KeyManagerServiceProvider::getInstance();
   }

    private function getUserPasswordsEncrypted(int $userId): array {
        try {
            $this->database->beginTransaction();
            $passwordsData = $this->database->getUserPasswords($userId);
            $this->database->commit();

            return array_map(fn($data) => new Password($data), $passwordsData);
        } catch (\Exception $e) {
            $this->database->rollback();
            error_log("Error fetching user passwords: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all passwords with decrypted values
     * @param int $userId
     * @return array<array{id: int, name: string, password: string|null, website: string|null}>
     */
    public function getUserPasswords(int $userId): array {
        $passwords = $this->getUserPasswordsEncrypted($userId);
        return array_map(function(Password $password) {
            return [
                'id' => $password->getId(),
                'name' => $password->getName(),
                'password' => $password->getDecryptedPassword(),
                'website' => $password->getWebsite()
            ];
        }, $passwords);
    }

    private function getPasswordEncrypted(int $passwordId, int $userId): ?Password {
        try {
            $this->database->beginTransaction();
            $data = $this->database->getPassword($passwordId, $userId);
            $this->database->commit();

            if (!$data) {
                return null;
            }

            return new Password($data);
        } catch (\Exception $e) {
            $this->database->rollback();
            error_log("Error fetching password: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get a specific password with decrypted value
     * @param int $passwordId
     * @param int $userId
     * @return array{id: int, name: string, password: string|null, website: string|null}|null
     */
    public function getPassword(int $passwordId, int $userId): ?array {
        $password = $this->getPasswordEncrypted($passwordId, $userId);
        if (!$password) {
            return null;
        }

        return [
            'id' => $password->getId(),
            'name' => $password->getName(),
            'password' => $password->getDecryptedPassword(),
            'website' => $password->getWebsite()
        ];
    }

    public function createPassword(int $userId, string $name, string $password, ?string $website = null): ?Password {
        try {
            if (!isset($_SESSION['aes_key'])) {
                error_log("No AES key found in session for user {$userId}");
                return null;
            }

            $this->database->beginTransaction();

            $encryptedPassword = $this->keyManager->encryptPassword($password, $_SESSION['aes_key']);
            $passwordId = $this->database->createPassword($userId, $name, $encryptedPassword, $website);

            if ($passwordId === false) {
                throw new \Exception("Failed to create password in database");
            }

            $this->database->commit();
            
            // Get the created password
            $createdPassword = $this->getPasswordEncrypted($passwordId, $userId);
            if (!$createdPassword) {
                throw new \Exception("Failed to retrieve created password");
            }
            
            return $createdPassword;
        } catch (\Exception $e) {
            $this->database->rollback();
            error_log("Error creating password: " . $e->getMessage());
            return null;
        }
    }

    public function updatePassword(int $passwordId, int $userId, string $name, string $password, ?string $website = null): ?Password {
        try {
            if (!isset($_SESSION['aes_key'])) {
                error_log("No AES key found in session for user {$userId}");
                return null;
            }

            $this->database->beginTransaction();

            $encryptedPassword = $this->keyManager->encryptPassword($password, $_SESSION['aes_key']);
            $success = $this->database->updatePassword($passwordId, $userId, $name, $encryptedPassword, $website);

            if (!$success) {
                throw new \Exception("Failed to update password");
            }

            $this->database->commit();
            return $this->getPasswordEncrypted($passwordId, $userId);
        } catch (\Exception $e) {
            $this->database->rollback();
            error_log("Error updating password: " . $e->getMessage());
            return null;
        }
    }

    public function deletePassword(int $passwordId, int $userId): bool {
        try {
            $this->database->beginTransaction();
            $result = $this->database->deletePassword($passwordId, $userId);
            $this->database->commit();
            return $result;
        } catch (\Exception $e) {
            $this->database->rollback();
            error_log("Error deleting password: " . $e->getMessage());
            return false;
        }
    }
}
