<?php

namespace App\Interfaces;

use App\Classes\Auth\User;

interface AuthenticationInterface {
    public function login(string $username, string $password): array;
    public function register(string $username, string $password): array;
    public function logout(): array;
    public function isLoggedIn(): bool;
    public function getCurrentUser(): ?User;
    public function getAesKey(): ?string;
    public function changePassword(int $userId, string $oldPassword, string $newPassword, string $confirmPassword): array;
} 