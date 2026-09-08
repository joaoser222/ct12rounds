<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;

trait HasMorphObjects
{
    protected static function bootHasMorphObjects(): void
    {
        static::registerAllMorphMaps();
        static::creating(function ($model) {
            $model->validateAllMorphTypes();
        });
        static::updating(function ($model) {
            $model->validateAllMorphTypes();
        });
    }

    /**
     * Register all morph maps defined in properties.
     */
    protected static function registerAllMorphMaps(): void
    {
        static $registered = [];

        $class = static::class;

        if (isset($registered[$class])) {
            return;
        }

        // Register maps from the $morphMaps property
        if (property_exists($class, 'morphMaps')) {
            $morphMaps = get_class_vars($class)['morphMaps'] ?? [];

            foreach ((array) $morphMaps as $field => $map) {
                if (! empty($map)) {
                    Relation::morphMap($map);
                }
            }
        }

        $registered[$class] = true;
    }

    /**
     * Validate all morph type fields.
     */
    protected function validateAllMorphTypes(): void
    {
        $configs = $this->getAllMorphConfigs();

        foreach ($configs as $config) {
            $field = $config['field'];
            $value = $this->getAttribute($field);

            if ($value !== null && ! $this->isMorphTypeAllowed($value, $config)) {
                throw ValidationException::withMessages([
                    $field => [$this->getMorphErrorMessage($value, $config)],
                ]);
            }
        }
    }

    /**
     * Return all morph configurations.
     */
    public function getAllMorphConfigs(): array
    {
        $configs = [];
        $class = static::class;

        // Get the defined maps
        $morphMaps = property_exists($class, 'morphMaps')
            ? (get_class_vars($class)['morphMaps'] ?? [])
            : [];

        // Get the defined permissions
        $allowedTypes = property_exists($class, 'allowedMorphTypes')
            ? static::$allowedMorphTypes
            : [];

        // For each field defined in morphMaps
        foreach ($morphMaps as $field => $map) {
            $configs[$field] = [
                'field' => $field,
                'map' => $map,
                'options' => array_keys($map),
                'allowed' => $allowedTypes[$field] ?? array_keys($map),
            ];
        }

        return $configs;
    }

    /**
     * Check if a type is allowed.
     */
    protected function isMorphTypeAllowed(string $type, array $config): bool
    {
        $allowed = $config['allowed'] ?? [];

        // If allowed is not explicitly defined, allow all from the map
        if (empty($allowed)) {
            $allowed = array_keys($config['map'] ?? []);
        }

        return in_array($type, $allowed);
    }

    /**
     * Return the error message.
     */
    protected function getMorphErrorMessage(string $invalidType, array $config): string
    {
        $field = $config['field'];
        $allowed = implode(', ', $config['allowed'] ?? []);

        return "Type '{$invalidType}' is not allowed for field '{$field}'. Allowed types: {$allowed}";
    }

    /**
     * Helper method to dynamically add an allowed type.
     */
    public function addAllowedMorphType(string $field, string $type): void
    {
        if (! property_exists($this, 'allowedMorphTypes')) {
            $this->allowedMorphTypes = [];
        }

        if (! isset($this->allowedMorphTypes[$field])) {
            $this->allowedMorphTypes[$field] = $this->getDefaultAllowedForField($field);
        }

        if (! in_array($type, $this->allowedMorphTypes[$field])) {
            $this->allowedMorphTypes[$field][] = $type;
        }
    }

    /**
     * Return the allowed types for a field.
     */
    public function getAllowedForField(string $field): array
    {
        $allowed = property_exists($this, 'allowedMorphTypes')
            ? ($this->allowedMorphTypes[$field] ?? [])
            : [];

        if (empty($allowed) && property_exists($this, 'morphMaps')) {
            $allowed = array_keys($this->morphMaps[$field] ?? []);
        }

        return $allowed;
    }

    /**
     * Return the default types for a field (all from the map).
     */
    protected function getDefaultAllowedForField(string $field): array
    {
        return property_exists($this, 'morphMaps')
            ? array_keys($this->morphMaps[$field] ?? [])
            : [];
    }
}
