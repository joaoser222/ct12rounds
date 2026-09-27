<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Accepts Brazilian phone numbers with or without the country code.
 *
 * Formatting is ignored, so "(11) 98888-8888" and "5511988888888" both pass.
 * Numbers without an area code are rejected: the country code is optional but
 * the DDD is not, otherwise the stored value would be ambiguous.
 */
class Phone implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_int($value)) {
            $fail('O campo :attribute deve conter um telefone válido.');

            return;
        }

        $length = strlen(preg_replace('/\D/', '', (string) $value) ?? '');

        if ($length < 10 || $length > 13) {
            $fail('O campo :attribute deve ter 10 ou 11 dígitos, incluindo o DDD.');
        }
    }
}
