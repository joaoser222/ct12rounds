<?php

namespace App\Models;

use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modality extends Model
{
    use HasFactory, HasVisibility;

    protected $table = 'modalities';

    protected $fillable = [
        'name',
        'color',
        'modality_category_id',
    ];

    public function modalityCategory(): BelongsTo
    {
        return $this->belongsTo(ModalityCategory::class);
    }

    public function classSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }
}
