<?php

namespace App\Models;

use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyLevel extends Model
{
    use HasFactory, HasVisibility;

    protected $table = 'loyalty_levels';

    protected $fillable = [
        'name',
        'min_months',
        'color',
        'description',
    ];

    protected $casts = [
        'min_months' => 'integer',
    ];

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }
}
