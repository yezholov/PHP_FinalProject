<?php

namespace App\Interfaces;

use App\Classes\Password\Password;

interface PasswordManagerInterface {

    /**
     * Get all passwords with decrypted values
     * @param int $userId
     * @return array<array{id: int, name: string, password: string|null, website: string|null}>
     */
    public function getUserPasswords(int $userId): array;

    /**
     * Get a specific password with decrypted value
     * @param int $passwordId
     * @param int $userId
     * @return array{id: int, name: string, password: string|null, website: string|null}|null
     */
    public function getPassword(int $passwordId, int $userId): ?array;

    /**
     * Create a new password entry
     * @param int $userId
     * @param string $name
     * @param string $password
     * @param string|null $website
     * @return Password|null
     */
    public function createPassword(int $userId, string $name, string $password, ?string $website = null): ?Password;

    /**
     * Update an existing password
     * @param int $passwordId
     * @param int $userId
     * @param string $name
     * @param string $password
     * @param string|null $website
     * @return Password|null
     */
    public function updatePassword(int $passwordId, int $userId, string $name, string $password, ?string $website = null): ?Password;

    /**
     * Delete a password
     * @param int $passwordId
     * @param int $userId
     * @return bool
     */
    public function deletePassword(int $passwordId, int $userId): bool;

    /**
     * Generate a new password using PasswordGenerator
     * @param int $length
     * @param int $uppercaseCount
     * @param int $lowercaseCount
     * @param int $numberCount
     * @param int $specialCharCount
     * @return string
     */
    public function generatePassword(
        int $length,
        int $uppercaseCount,
        int $lowercaseCount,
        int $numberCount,
        int $specialCharCount
    ): string;
} 