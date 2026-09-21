<?php

namespace App\Models;

use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSchedule extends Model
{
    use HasFactory, HasVisibility;

    protected $table = 'class_schedules';

    protected $fillable = [
        'modality_id',
        'week_day',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'week_day' => 'integer',
        'start_time' => 'date:H:i',
        'end_time' => 'date:H:i',
    ];

    public function modality(): BelongsTo
    {
        return $this->belongsTo(Modality::class);
    }
}
