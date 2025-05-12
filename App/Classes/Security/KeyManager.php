<?php

namespace App\Classes\Security;

use App\Interfaces\KeyManagerInterface;

class KeyManager implements KeyManagerInterface
{
    private const AES_METHOD = 'AES-256-ECB';
    private const EXPECTED_KEY_LENGTH = 32; // For AES-256

    /**
     * Adjust password to key
     * @param string $password
     * @return string
     */
    private function adjustPasswordToKey(string $password): string
    {
        $currentLength = strlen($password);
        if ($currentLength === self::EXPECTED_KEY_LENGTH) {
            return $password;
        } elseif ($currentLength < self::EXPECTED_KEY_LENGTH) {
            // Pad with null bytes
            return str_pad($password, self::EXPECTED_KEY_LENGTH, "\0", STR_PAD_RIGHT);
        } else {
            // Truncate
            return substr($password, 0, self::EXPECTED_KEY_LENGTH);
        }
    }
    /**
     * Encrypt key with plain password
     * @param string $aesKey
     * @param string $password
     * @throws \Exception
     * @return string
     */
    public function encryptKey(string $aesKey, string $password): string
    {
        // Use plain password adjusted to 32 bytes as the key
        $encryptionKey = $this->adjustPasswordToKey($password);

        $encrypted = openssl_encrypt(
            $aesKey,
            self::AES_METHOD,
            $encryptionKey,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING
        );

        if ($encrypted === false) {
            $error = openssl_error_string();
            error_log("AES Encryption Error: " . $error);
            throw new \Exception('Key encryption failed.');
        }

        return base64_encode($encrypted);
    }
    /**
     * Decrypt key with plain password
     * @param string $encryptedData
     * @param string $password
     * @return bool|string
     */
    public function decryptKey(string $encryptedData, string $password)
    {
        // Use plain password adjusted to 32 bytes as the key
        $decryptionKey = $this->adjustPasswordToKey($password);
        $encrypted = base64_decode($encryptedData);

        if ($encrypted === false) {
            return false; // Invalid base64
        }

        $decrypted = openssl_decrypt(
            $encrypted,
            self::AES_METHOD,
            $decryptionKey,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING
        );

        return $decrypted;
    }
    /**
     * Encrypt password with AES-key
     * @param string $password
     * @param string $aesKey
     * @throws \Exception
     * @return string
     */
    public function encryptPassword(string $password, string $aesKey): string
    {
        $encrypted = openssl_encrypt(
            $password,
            self::AES_METHOD,
            $aesKey,
            OPENSSL_RAW_DATA
        );

        if ($encrypted === false) {
            $error = openssl_error_string();
            error_log("AES Password Encryption Error: " . $error);
            throw new \Exception('Password encryption failed.');
        }

        return base64_encode($encrypted);
    }
    /**
     * Decrypt password with AES-key
     * @param string $encryptedPassword
     * @param string $aesKey
     * @return string
     */
    public function decryptPassword(string $encryptedPassword, string $aesKey): string
    {
        $decoded = base64_decode($encryptedPassword);
        if ($decoded === false) {
            throw new \Exception('Invalid encrypted password format.');
        }

        $decrypted = openssl_decrypt(
            $decoded,
            self::AES_METHOD,
            $aesKey,
            OPENSSL_RAW_DATA
        );

        if ($decrypted === false) {
            $error = openssl_error_string();
            error_log("AES Password Decryption Error: " . $error);
            throw new \Exception('Password decryption failed.');
        }

        return $decrypted;
    }
    /**
     * Generate a secure key
     * @throws \Exception
     * @return string
     */
    public function generateKey(): string
    {
        try {
            return random_bytes(self::EXPECTED_KEY_LENGTH);
        } catch (\Exception $e) {
            error_log("Key generation error: " . $e->getMessage());
            throw new \Exception('Failed to generate secure key.');
        }
    }
}
