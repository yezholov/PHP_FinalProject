<?php

namespace App\Classes\Password;

use App\Classes\Security\KeyManager;
use App\Services\KeyManagerServiceProvider;

/**
 * Class Password
 */
class Password {
    public ?int $id = null;
    public ?int $user_id = null;
    public ?string $name = null;
    public ?string $password_encrypted = null;
    public ?string $website = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    private ?KeyManager $keyManager = null;

    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->user_id = isset($data['user_id']) ? (int)$data['user_id'] : null;
        $this->name = $data['name'] ?? null;
        $this->password_encrypted = $data['password_encrypted'] ?? null;
        $this->website = $data['website'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getUserId(): ?int {
        return $this->user_id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function getWebsite(): ?string {
        return $this->website;
    }
    /**
     * Get the decrypted password
     * @return string|null
     */
    public function getDecryptedPassword(): ?string {
        if (!$this->password_encrypted || !isset($_SESSION['aes_key'])) {
            return null;
        }

        try {
            $this->keyManager = $this->keyManager ?? KeyManagerServiceProvider::getInstance();
            return $this->keyManager->decryptPassword($this->password_encrypted, $_SESSION['aes_key']);
        } catch (\Exception $e) {
            error_log("Error decrypting password: " . $e->getMessage());
            return null;
        }
    }

} 