<?php

namespace App\Traits;

use App\Enums\Visibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasVisibility
{
    /**
     * Initialize the trait.
     */
    public function initializeHasVisibility(): void
    {
        // Add the Visibility Enum cast
        if (! isset($this->casts['visibility'])) {
            $this->casts['visibility'] = Visibility::class;
        }

        // Add visibility to fillable if not present
        if (! in_array('visibility', $this->fillable)) {
            $this->fillable[] = 'visibility';
        }

        // Set the default value
        if (! isset($this->attributes['visibility'])) {
            $this->attributes['visibility'] = Visibility::VISIBLE;
        }
    }

    /**
     * Validate that the visibility value is valid.
     */
    public function isValidVisibility(): bool
    {
        return Visibility::isValid($this->visibility);
    }

    /**
     * Return the Enum object.
     */
    public function getVisibilityEnum(): ?Visibility
    {
        return Visibility::tryFrom($this->visibility);
    }

    /**
     * Label accessor.
     */
    public function getVisibilityLabelAttribute(): string
    {
        return $this->getVisibilityEnum()?->label() ?? $this->visibility;
    }

    /**
     * Mutator with validation.
     */
    public function setVisibilityAttribute(string|Visibility $value): void
    {
        if ($value instanceof Visibility) {
            $value = $value->value;
        }

        if (! Visibility::isValid($value)) {
            throw new \InvalidArgumentException(
                "Invalid value for visibility: '{$value}'. Allowed values: "
                .implode(', ', Visibility::values())
            );
        }

        $this->attributes['visibility'] = $value;
    }

    // Verification methods

    public function isVisible(): bool
    {
        return $this->visibility === Visibility::VISIBLE->value;
    }

    public function isHidden(): bool
    {
        return $this->visibility === Visibility::HIDDEN->value;
    }

    public function isArchived(): bool
    {
        return $this->visibility === Visibility::ARCHIVED->value;
    }

    // Scopes
    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('visibility', Visibility::VISIBLE->value);
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeHidden(Builder $query): Builder
    {
        return $query->where('visibility', Visibility::HIDDEN->value);
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('visibility', Visibility::ARCHIVED->value);
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->where('visibility', '!=', Visibility::ARCHIVED->value);
    }

    // Static methods for use in validations

    /**
     * @return array<string, array<string, string>>
     */
    public static function getVisibilityOptions(): array
    {
        return Visibility::options();
    }

    /**
     * @return array<int, string>
     */
    public static function getValidVisibilityValues(): array
    {
        return Visibility::values();
    }
}
