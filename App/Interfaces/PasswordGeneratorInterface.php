<?php

namespace App\Interfaces;

interface PasswordGeneratorInterface
{
    /**
     * Generates a password based on specified criteria.
     *
     * @param int $uppercaseCount Number of uppercase letters.
     * @param int $lowercaseCount Number of lowercase letters.
     * @param int $numberCount Number of digits.
     * @param int $specialCharCount Number of special characters.
     * @return string The generated password.
     * @throws \InvalidArgumentException If parameters are invalid (e.g., sum of counts doesn't match length, or negative counts).
     * @throws \Exception If random generation fails for some reason.
     */
    public function generate(
        int $uppercaseCount,
        int $lowercaseCount,
        int $numberCount,
        int $specialCharCount
    ): string;
}
