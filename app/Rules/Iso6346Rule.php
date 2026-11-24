<?php

namespace App\Rules;

use App\Support\Iso6346;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Iso6346Rule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail("The {$attribute} must be a valid container number string.");

            return;
        }

        $clean = strtoupper(preg_replace('/\s+/', '', $value));

        // 1. Structure check: 3-letter owner code, category U/J/Z, 6 serial digits, 1 check digit
        if (! preg_match('/^[A-Z]{3}[UJZ]\d{6}\d$/', $clean)) {
            $fail("The {$attribute} must be a valid ISO 6346 container number (3-letter owner code, category U/J/Z, 6 digits, and 1 check digit).");

            return;
        }

        // 2. Compute check digit per ISO 6346 standard
        $prefix10 = substr($clean, 0, 10);
        $actualCheck = (int) substr($clean, 10, 1);

        try {
            $expectedCheck = Iso6346::calculateCheckDigit($prefix10);
        } catch (\Throwable) {
            $fail("The {$attribute} contains an invalid character.");

            return;
        }

        if ($expectedCheck !== $actualCheck) {
            $fail("The {$attribute} has an invalid ISO 6346 check digit (expected {$expectedCheck}, got {$actualCheck}).");
        }
    }
}
