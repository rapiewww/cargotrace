<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class AwbRule implements ValidationRule
{
    /**
     * Run the validation rule.
     * An air waybill consists of 11 digits:
     * - 3-digit airline prefix
     * - 7-digit serial number
     * - 1 check digit equal to serial mod 7
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) && ! is_numeric($value)) {
            $fail("The {$attribute} must be a valid air waybill number string.");

            return;
        }

        // Strip spaces, dashes, or separators
        $clean = preg_replace('/[\s\-]+/', '', (string) $value);

        if (! preg_match('/^\d{11}$/', $clean)) {
            $fail("The {$attribute} must be an 11-digit air waybill number (3-digit airline prefix + 7-digit serial + 1 check digit).");

            return;
        }

        $serial = (int) substr($clean, 3, 7);
        $actualCheck = (int) substr($clean, 10, 1);
        $expectedCheck = $serial % 7;

        if ($expectedCheck !== $actualCheck) {
            $fail("The {$attribute} has an invalid AWB check digit (expected {$expectedCheck}, got {$actualCheck}).");
        }
    }
}
