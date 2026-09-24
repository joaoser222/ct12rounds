<?php

namespace App\Services\Gateway;

final class GatewayCustomerSanitizer
{
    /**
     * @var array<int, string>
     */
    private const PREPOSITIONS = [
        'a',
        'à',
        'as',
        'ao',
        'aos',
        'com',
        'como',
        'da',
        'das',
        'de',
        'do',
        'dos',
        'em',
        'entre',
        'na',
        'nas',
        'no',
        'nos',
        'para',
        'perante',
        'por',
        'sem',
        'sob',
        'sobre',
        'até',
        'desde',
        'contra',
    ];

    /**
     * @var array<int, string>
     */
    private const NAME_ACRONYMS = [
        'API',
        'CNPJ',
        'CPF',
        'EIRELI',
        'EPP',
        'LTDA',
        'ME',
        'PIX',
        'RG',
        'SA',
    ];

    /**
     * @param  array<string, mixed>  $customer
     * @return array<string, string>
     */
    public function sanitize(array $customer): array
    {
        $attributes = [
            'name' => $this->sanitizeName($this->stringValue($customer, ['name']) ?? 'Cliente Asaas'),
            'email' => $this->sanitizeEmail($this->stringValue($customer, ['email'])),
            'phone' => $this->sanitizeDigits($this->stringValue($customer, ['mobilePhone', 'phone'])),
            'document' => $this->sanitizeDigits($this->stringValue($customer, ['cpfCnpj'])),
            'address' => $this->sanitizeAddressLine($this->stringValue($customer, ['address'])),
            'address_number' => $this->sanitizeAddressNumber($this->stringValue($customer, ['addressNumber'])),
            'address_complement' => $this->sanitizeAddressLine($this->stringValue($customer, ['complement'])),
            'address_district' => $this->sanitizeAddressLine($this->stringValue($customer, ['province', 'addressDistrict'])),
            'address_postal_code' => $this->sanitizePostalCode($this->stringValue($customer, ['postalCode'])),
            'address_city' => $this->sanitizeAddressLine($this->cityValue($customer)),
            'address_state' => $this->sanitizeState($this->stringValue($customer, ['state'])),
        ];

        return array_filter(
            $attributes,
            static fn (?string $value): bool => $value !== null && $value !== '',
        );
    }

    public function sanitizeName(?string $value): string
    {
        return $this->formatWords($value ?? '', true);
    }

    public function sanitizeAddressLine(?string $value): ?string
    {
        $value = $this->normalizeWhitespace($value ?? '');

        return $value === '' ? null : $this->formatWords($value, false);
    }

    public function sanitizeAddressNumber(?string $value): ?string
    {
        $value = mb_strtoupper($this->normalizeWhitespace($value ?? ''), 'UTF-8');

        return $value === '' ? null : $value;
    }

    public function sanitizeEmail(?string $value): ?string
    {
        $value = mb_strtolower($this->normalizeWhitespace($value ?? ''), 'UTF-8');

        return $value === '' ? null : $value;
    }

    public function sanitizeDigits(?string $value): ?string
    {
        $value = preg_replace('/\D/', '', $value ?? '');

        return $value === '' ? null : $value;
    }

    public function sanitizePostalCode(?string $value): ?string
    {
        return $this->sanitizeDigits($value);
    }

    public function sanitizeState(?string $value): ?string
    {
        $value = mb_strtoupper((string) preg_replace('/[^a-zA-Z]/', '', $value ?? ''), 'UTF-8');

        return $value === '' ? null : mb_substr($value, 0, 2, 'UTF-8');
    }

    /**
     * @param  array<string, mixed>  $customer
     * @param  array<string, string>  $sanitized
     */
    public function hasRemoteChanges(array $customer, array $sanitized): bool
    {
        foreach ($sanitized as $attribute => $value) {
            $remoteValue = $this->remoteValue($customer, $attribute);

            if ($remoteValue === null || $remoteValue !== $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $customer
     */
    private function remoteValue(array $customer, string $attribute): ?string
    {
        $value = match ($attribute) {
            'name' => $this->stringValue($customer, ['name']),
            'email' => $this->stringValue($customer, ['email']),
            'phone' => $this->stringValue($customer, ['mobilePhone', 'phone']),
            'document' => $this->stringValue($customer, ['cpfCnpj', 'document']),
            'address' => $this->stringValue($customer, ['address']),
            'address_number' => $this->stringValue($customer, ['addressNumber', 'address_number']),
            'address_complement' => $this->stringValue($customer, ['complement', 'address_complement']),
            'address_district' => $this->stringValue($customer, ['province', 'addressDistrict', 'address_district']),
            'address_postal_code' => $this->stringValue($customer, ['postalCode', 'address_postal_code']),
            'address_city' => $this->cityValue($customer) ?? $this->stringValue($customer, ['address_city']),
            'address_state' => $this->stringValue($customer, ['state', 'address_state']),
            default => null,
        };

        if ($value === null) {
            return null;
        }

        return match ($attribute) {
            'phone', 'document', 'address_postal_code' => $this->sanitizeDigits($value),
            default => $this->normalizeWhitespace($value),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     */
    private function stringValue(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $data[$key] ?? null;

            if (is_string($value) || is_numeric($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function cityValue(array $data): ?string
    {
        $cityName = $this->stringValue($data, ['cityName']);

        if ($cityName !== null) {
            return $cityName;
        }

        $city = $data['city'] ?? null;

        return is_string($city) ? $city : null;
    }

    private function formatWords(string $value, bool $preserveAcronyms): string
    {
        $value = $this->normalizeWhitespace($value);

        if ($value === '') {
            return '';
        }

        $words = preg_split('/\s+/u', $value) ?: [];
        $formatted = [];

        foreach ($words as $index => $word) {
            $plainWord = trim($word, ".,;:!?()[]{}\"'");
            $lowerWord = mb_strtolower($plainWord, 'UTF-8');
            $upperWord = mb_strtoupper($plainWord, 'UTF-8');

            if ($index > 0 && in_array($lowerWord, self::PREPOSITIONS, true)) {
                $formatted[] = mb_strtolower($word, 'UTF-8');

                continue;
            }

            if ($preserveAcronyms && in_array($upperWord, self::NAME_ACRONYMS, true)) {
                $formatted[] = $upperWord;

                continue;
            }

            $formatted[] = mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
        }

        return implode(' ', $formatted);
    }

    private function normalizeWhitespace(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }
}
