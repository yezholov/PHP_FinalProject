<?php

namespace App\Services;

use App\Classes\Security\KeyManager;
use App\Interfaces\KeyManagerInterface;

class KeyManagerServiceProvider
{
    private static ?KeyManagerInterface $instance = null;

    public static function getInstance(): KeyManagerInterface
    {
        if (self::$instance === null) {
            self::$instance = new KeyManager();
        }
        return self::$instance;
    }

    public static function setInstance(KeyManagerInterface $instance): void
    {
        self::$instance = $instance;
    }
} 