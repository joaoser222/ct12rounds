<?php

namespace App\Models;

use App\Enums\Audience;
use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModalityCategory extends Model
{
    use HasFactory, HasVisibility;

    protected $table = 'modality_categories';

    protected $fillable = [
        'name',
        'slug',
        'audience',
    ];

    protected $casts = [
        'audience' => Audience::class,
    ];

    public function modalities(): HasMany
    {
        return $this->hasMany(Modality::class);
    }
}
