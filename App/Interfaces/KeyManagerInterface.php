<?php

namespace App\Interfaces;

interface KeyManagerInterface
{
    /**
     * Encrypts an AES key using a password
     *
     * @param string $aesKey Raw AES key to encrypt
     * @param string $password Password to use for encryption
     * @return string Base64 encoded encrypted key
     * @throws \Exception If encryption fails
     */
    public function encryptKey(string $aesKey, string $password): string;

    /**
     * Decrypts an encrypted AES key using a password
     *
     * @param string $encryptedData Base64 encoded encrypted key
     * @param string $password Password to use for decryption
     * @return string|false Decrypted key or false on failure
     */
    public function decryptKey(string $encryptedData, string $password);

    /**
     * Generates a new AES key
     *
     * @return string Raw AES key
     * @throws \Exception If key generation fails
     */
    public function generateKey(): string;
} 