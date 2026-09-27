<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Accepts only Brazilian CPFs, validating both check digits.
 *
 * Mirrors resources/js/plugins/validators.ts so the public API rejects the same
 * documents the registration form already refuses to submit.
 */
class Cpf implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::isValid($value)) {
            $fail('O campo :attribute deve conter um CPF válido.');
        }
    }

    public static function isValid(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $digits = preg_replace('/\D/', '', $value) ?? '';

        if (strlen($digits) !== 11 || preg_match('/^(\d)\1{10}$/', $digits) === 1) {
            return false;
        }

        foreach ([9, 10] as $position) {
            $sum = 0;

            for ($index = 0; $index < $position; $index++) {
                $sum += (int) $digits[$index] * (($position + 1) - $index);
            }

            $remainder = $sum % 11;
            $checkDigit = $remainder < 2 ? 0 : 11 - $remainder;

            if ((int) $digits[$position] !== $checkDigit) {
                return false;
            }
        }

        return true;
    }
}
