<?php

namespace App\Models;

use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class Trainer extends Model
{
    use HasVisibility;

    protected $table = 'trainers';

    protected $fillable = [
        'name',
        'email',
        'document',
        'birth_date',
        'phone',
        'gender',
        'profile_image',
        'address',
        'address_number',
        'address_complement',
        'address_state',
        'address_city',
        'address_district',
        'address_postal_code',
    ];

    public function modalities()
    {
        return $this->hasMany(TrainerModality::class);
    }
}
