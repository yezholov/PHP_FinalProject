<?php

namespace App\Interfaces;

use App\Classes\Auth\User;

interface UserRepositoryInterface {
    public function findById(int $id): ?User;
    public function findByUsername(string $username): ?User;
    public function create(User $user, string $passwordHash): int|false;
    public function getUserCredentials(string $username): ?array;
} 