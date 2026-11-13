<?php

namespace App\Support;

use InvalidArgumentException;

class Iso6346
{
    /**
     * Letter to numeric value mapping per ISO 6346 (multiples of 11 are omitted).
     */
    protected const LETTER_VALUES = [
        'A' => 10, 'B' => 12, 'C' => 13, 'D' => 14, 'E' => 15, 'F' => 16, 'G' => 17, 'H' => 18, 'I' => 19, 'J' => 20,
        'K' => 21, 'L' => 23, 'M' => 24, 'N' => 25, 'O' => 26, 'P' => 27, 'Q' => 28, 'R' => 29, 'S' => 30, 'T' => 31,
        'U' => 32, 'V' => 34, 'W' => 35, 'X' => 36, 'Y' => 37, 'Z' => 38,
    ];

    /**
     * Calculate the 1-digit check digit for a 10-character container prefix (4 letters + 6 digits).
     */
    public static function calculateCheckDigit(string $prefix10): int
    {
        $prefix = strtoupper(trim($prefix10));
        if (strlen($prefix) !== 10) {
            throw new InvalidArgumentException("ISO 6346 check digit requires exactly 10 characters, [{$prefix10}] given.");
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $char = $prefix[$i];
            if (ctype_digit($char)) {
                $val = (int) $char;
            } elseif (isset(self::LETTER_VALUES[$char])) {
                $val = self::LETTER_VALUES[$char];
            } else {
                throw new InvalidArgumentException("Invalid character [{$char}] in container prefix [{$prefix10}].");
            }

            $sum += $val * (1 << $i);
        }

        $remainder = $sum % 11;

        return $remainder % 10;
    }

    /**
     * Generate a full 11-character ISO 6346 compliant container number with valid check digit.
     *
     * @param  string  $ownerCode  3 or 4 uppercase letters (e.g. 'MSK' -> 'MSKU', or 'MSKU')
     * @param  int|string  $serial  6-digit serial number (0 to 999999)
     */
    public static function generate(string $ownerCode = 'MSKU', int|string $serial = 100000): string
    {
        $cleanOwner = strtoupper(preg_replace('/[^A-Za-z]/', '', $ownerCode));
        // Under ISO 6346, freight container prefixes consist of a 3-letter owner code + 'U' category identifier
        $cleanOwner = substr(str_pad($cleanOwner, 3, 'X'), 0, 3).'U';

        $serialFormatted = sprintf('%06d', (int) $serial);
        $prefix10 = $cleanOwner.$serialFormatted;
        $checkDigit = self::calculateCheckDigit($prefix10);

        return $prefix10.$checkDigit;
    }

    /**
     * Validate whether an 11-character container number has a valid ISO 6346 check digit.
     */
    public static function isValid(string $containerNumber): bool
    {
        $clean = strtoupper(trim($containerNumber));
        if (strlen($clean) !== 11) {
            return false;
        }

        $prefix10 = substr($clean, 0, 10);
        $check = (int) substr($clean, 10, 1);

        try {
            return self::calculateCheckDigit($prefix10) === $check;
        } catch (\Throwable) {
            return false;
        }
    }
}
