<?php

namespace App\Services;

use App\Models\Setting;
use InvalidArgumentException;

/**
 * Normalizes contact identifiers before they reach the database.
 *
 * Phone numbers are stored in international form (country code included) so
 * that the value is unambiguous regardless of how the user typed it. The
 * country code comes from the `default_country_code` setting instead of being
 * hardcoded.
 */
class ContactNormalizer
{
    private const COUNTRY_CODE_SETTING = 'default_country_code';

    private const FALLBACK_COUNTRY_CODE = '55';

    /**
     * Brazilian national lengths: 10 digits (area code + landline) and
     * 11 digits (area code + mobile, which always carries the extra 9).
     */
    private const NATIONAL_LENGTHS = [10, 11];

    /**
     * Already international: 12 digits (country code + landline) and
     * 13 digits (country code + mobile).
     */
    private const INTERNATIONAL_LENGTHS = [12, 13];

    /**
     * @throws InvalidArgumentException when the value cannot be a Brazilian phone number.
     */
    public function phone(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string) $value) ?? '';

        if ($digits === '') {
            return null;
        }

        $length = strlen($digits);

        if (in_array($length, self::NATIONAL_LENGTHS, true)) {
            return $this->countryCode().$digits;
        }

        if (in_array($length, self::INTERNATIONAL_LENGTHS, true)) {
            return str_starts_with($digits, $this->countryCode())
                ? $digits
                : $this->countryCode().$digits;
        }

        throw new InvalidArgumentException(sprintf(
            'O telefone deve ter 10 ou 11 dígitos, incluindo o DDD. Recebido: %d dígitos.',
            $length
        ));
    }

    /**
     * Strips formatting from a document. Length and check digits are the
     * responsibility of the validation layer, which knows whether the field
     * accepts a CPF or also a CNPJ.
     */
    public function document(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string) $value) ?? '';

        return $digits === '' ? null : $digits;
    }

    public function countryCode(): string
    {
        $stored = Setting::query()
            ->where('name', self::COUNTRY_CODE_SETTING)
            ->value('content');

        if (! is_string($stored) && ! is_int($stored)) {
            return self::FALLBACK_COUNTRY_CODE;
        }

        $digits = preg_replace('/\D/', '', (string) $stored) ?? '';

        return $digits === '' ? self::FALLBACK_COUNTRY_CODE : $digits;
    }
}
