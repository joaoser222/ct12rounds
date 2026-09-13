<?php

namespace App\Models;

use Database\Factories\LandingAdminUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class LandingAdminUser extends Authenticatable
{
    /** @use HasFactory<LandingAdminUserFactory> */
    use HasFactory;

    protected $table = 'landing_admin_users';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function permissionsVersion(): string
    {
        return hash('sha256', implode('|', [
            $this->id,
            $this->updated_at?->toISOString() ?? '',
        ]));
    }
}