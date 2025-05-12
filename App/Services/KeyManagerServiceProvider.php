<?php

namespace App\Services;

use App\Classes\Security\KeyManager;
use App\Interfaces\KeyManagerInterface;

class KeyManagerServiceProvider
{
    private static ?KeyManagerInterface $instance = null;
    /**
     * Get the instance
     * @return KeyManagerInterface|null
     */
    public static function getInstance(): KeyManagerInterface
    {
        if (self::$instance === null) {
            self::$instance = new KeyManager();
        }
        return self::$instance;
    }
    /**
     * Set the instance
     * @param KeyManagerInterface $instance
     */
    public static function setInstance(KeyManagerInterface $instance): void
    {
        self::$instance = $instance;
    }
} 