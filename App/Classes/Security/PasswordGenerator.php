<?php

namespace App\Classes\Security;

use App\Interfaces\PasswordGeneratorInterface;

class PasswordGenerator implements PasswordGeneratorInterface
{
    private const LOWERCASE_CHARS = 'abcdefghijklmnopqrstuvwxyz';
    private const UPPERCASE_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const NUMBER_CHARS = '0123456789';
    private const SPECIAL_CHARS = '!@#$%^&*()_+-=[]{}|;:,.<>/?';

    /**
     * {@inheritdoc}
     */
    public function generate(
        int $uppercaseCount,
        int $lowercaseCount,
        int $numberCount,
        int $specialCharCount
    ): string {
        if ($uppercaseCount < 0 || $lowercaseCount < 0 || $numberCount < 0 || $specialCharCount < 0) {
            throw new \InvalidArgumentException('Character counts cannot be negative.');
        }

        $passwordChars = [];

        // Add uppercase letters
        $passwordChars = array_merge($passwordChars, $this->getRandomChars(self::UPPERCASE_CHARS, $uppercaseCount));
        // Add lowercase letters
        $passwordChars = array_merge($passwordChars, $this->getRandomChars(self::LOWERCASE_CHARS, $lowercaseCount));
        // Add numbers
        $passwordChars = array_merge($passwordChars, $this->getRandomChars(self::NUMBER_CHARS, $numberCount));
        // Add special characters
        $passwordChars = array_merge($passwordChars, $this->getRandomChars(self::SPECIAL_CHARS, $specialCharCount));
        
        // Shuffle the characters to ensure random order
        shuffle($passwordChars);

        return implode('', $passwordChars);
    }

    /**
     * Gets a specified number of random characters from a character set.
     *
     * @param string $charSet The set of characters to choose from.
     * @param int $count The number of random characters to get.
     * @return array An array of random characters.
     * @throws \Exception If random_int fails.
     */
    private function getRandomChars(string $charSet, int $count): array
    {
        $chars = [];
        $maxIndex = strlen($charSet) - 1;
        if ($maxIndex < 0 && $count > 0) { // Empty charset but characters requested
            throw new \InvalidArgumentException('Cannot select characters from an empty set.');
        }
        for ($i = 0; $i < $count; $i++) {
            if ($maxIndex < 0) break; // Should not happen if count > 0 due to above check, but as safeguard
            $chars[] = $charSet[random_int(0, $maxIndex)];
        }
        return $chars;
    }
} 