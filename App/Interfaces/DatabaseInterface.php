<?php

namespace App\Interfaces;

interface DatabaseInterface {
    public function getConnection();
    public function beginTransaction(): bool;
    public function commit(): bool;
    public function rollback(): bool;
} 