<?php

namespace App\Interfaces;

use App\Classes\Auth\User;

interface UserRepositoryInterface {
    /**
     * Get a user by their ID
     * @param int $id
     * @return User|null
     */
    public function findById(int $id): ?User;

    /**
     * Get a user by their username
     * @param string $username
     * @return User|null
     */
    public function findByUsername(string $username): ?User;

    /**
     * Get a user's credentials
     * @param string $username
     * @return array|null
     */
    public function getUserCredentials(string $username): ?array;

    /**
     * Create a new user with a key
     * @param string $username
     * @param string $passwordHash
     * @param string $encryptedKey
     * @return int|false
     */
    public function createUserWithKey(string $username, string $passwordHash, string $encryptedKey): int|false;

    /**
     * Get a user's key
     * @param int $userId
     * @return string|null
     */
    public function getUserKey(int $userId): ?string;

    /**
     * Update a user's password and key
     * @param int $userId
     * @param string $newPasswordHash
     * @param string $newEncryptedKey
     * @return bool
     */
    public function updatePasswordAndKey(int $userId, string $newPasswordHash, string $newEncryptedKey): bool;
} 