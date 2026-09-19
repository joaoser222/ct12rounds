<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /**
     * @var array<string, string>
     */
    public const SELECT_TABLES = [
        'financial-account' => 'financial_accounts',
        'financial-category' => 'financial_categories',
    ];

    protected $table = 'settings';

    protected $fillable = [
        'name',
        'label',
        'content',
        'object_type',
        'group',
    ];

    protected $casts = [
        'content' => 'json',
    ];

    public function isSelection(): bool
    {
        return str_starts_with($this->object_type, 'select:');
    }

    public function selectObjectName(): ?string
    {
        if (! $this->isSelection()) {
            return null;
        }

        return substr($this->object_type, strlen('select:')) ?: null;
    }

    public function selectTable(): ?string
    {
        $objectName = $this->selectObjectName();

        if ($objectName === null) {
            return null;
        }

        return self::SELECT_TABLES[$objectName] ?? null;
    }
}
