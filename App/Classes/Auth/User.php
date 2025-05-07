<?php

namespace App\Classes\Auth;

/**
 * Represents a User entity (Data Transfer Object).
 * Contains only user data properties and simple getters.
 */
class User {
    public ?int $id = null;
    public ?string $username = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->username = $data['username'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    /**
     * Gets the User ID.
     */
    public function getId(): ?int {
        return $this->id;
    }

}