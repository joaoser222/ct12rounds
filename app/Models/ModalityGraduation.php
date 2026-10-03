<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModalityGraduation extends Model
{
    use HasFactory;

    protected $fillable = [
        'modality_id',
        'name',
        'position',
    ];

    public function modality(): BelongsTo
    {
        return $this->belongsTo(Modality::class);
    }

    public function clientGraduations(): HasMany
    {
        return $this->hasMany(ClientGraduation::class);
    }
}
