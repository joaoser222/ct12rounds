<?php

namespace App\Models;

use App\Enums\AudienceCategory;
use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modality extends Model
{
    use HasFactory, HasVisibility;

    protected $table = 'modalities';

    protected $fillable = [
        'name',
        'color',
        'audience_category',
    ];

    protected $casts = [
        'audience_category' => AudienceCategory::class,
    ];

    public function classSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }
}
