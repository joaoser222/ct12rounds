<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainerModality extends Model
{
    protected $table = 'trainer_modalities';

    protected $fillable = [
        'trainer_id',
        'modality_id',
    ];

    public function modality()
    {
        return $this->belongsTo(Modality::class);
    }

    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }
}