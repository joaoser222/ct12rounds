<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientGraduation extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'modality_graduation_id',
        'promoted_at',
    ];

    protected function casts(): array
    {
        return [
            'promoted_at' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function modalityGraduation(): BelongsTo
    {
        return $this->belongsTo(ModalityGraduation::class);
    }
}
