<?php

namespace App\Support;

use InvalidArgumentException;

class Awb
{
    /**
     * Generate a valid 11-digit Air Waybill number:
     * 3-digit airline prefix + 7-digit serial + 1 check digit (serial % 7).
     */
    public static function generate(string|int $airlinePrefix3, int $serial7): string
    {
        $prefixClean = preg_replace('/\D/', '', (string) $airlinePrefix3);
        if ($prefixClean === '') {
            throw new InvalidArgumentException('Airline prefix must contain digits.');
        }

        $prefix = substr(str_pad($prefixClean, 3, '0', STR_PAD_LEFT), 0, 3);
        $serialNormalized = abs($serial7) % 10000000;
        $serialFormatted = sprintf('%07d', $serialNormalized);
        $checkDigit = $serialNormalized % 7;

        return $prefix.$serialFormatted.$checkDigit;
    }

    /**
     * Validate an 11-digit Air Waybill number and its mod-7 check digit.
     */
    public static function isValid(?string $awb): bool
    {
        if ($awb === null || trim($awb) === '') {
            return false;
        }

        $clean = preg_replace('/[\s\-]+/', '', $awb);
        if (! preg_match('/^\d{11}$/', $clean)) {
            return false;
        }

        $serial = (int) substr($clean, 3, 7);
        $actualCheck = (int) substr($clean, 10, 1);

        return ($serial % 7) === $actualCheck;
    }

    /**
     * Format an 11-digit AWB as "PPP-SSSSSSSC".
     */
    public static function format(?string $awb): ?string
    {
        if ($awb === null || trim($awb) === '') {
            return null;
        }

        $clean = preg_replace('/[\s\-]+/', '', $awb);
        if (strlen($clean) === 11) {
            return substr($clean, 0, 3).'-'.substr($clean, 3);
        }

        return $awb;
    }
}
