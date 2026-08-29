<?php

namespace App\Models;

use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Modality extends Model
{
    use HasVisibility;

    protected $table = 'modalities';

    protected $fillable = [
        'name',
        'color',
        'icon',
    ];

    /**
     * @var array<int, string>
     */
    protected $appends = ['icon_url'];

    public function getIconUrlAttribute(): ?string
    {
        return $this->icon !== null
            ? Storage::disk('public')->url($this->icon)
            : null;
    }
}
