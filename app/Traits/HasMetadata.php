<?php

namespace App\Traits;

trait HasMetadata
{
    abstract public function label(): string;

    public function color(): string
    {
        return 'secondary';
    }

    /**
     * List of fields that will be returned by options().
     * Can be overridden in the enum that uses this trait.
     */
    protected static function fields(): array
    {
        return ['label', 'value', 'color'];
    }

    /**
     * Return all values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Return array for selects (value => [fields defined in fields()]).
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $data = [];

            foreach (static::fields() as $field) {
                $data[$field] = $field === 'value'
                    ? $case->value
                    : $case->{$field}();
            }

            $options[$case->value] = $data;
        }

        return $options;
    }

    /**
     * Check if a value is valid.
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::values());
    }
}
