<?php

namespace App\Models;

use Database\Factories\LandingContentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingContent extends Model
{
    /** @use HasFactory<LandingContentFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $table = 'landing_contents';

    protected $fillable = [
        'project',
        'draft_html',
        'published_html',
        'status',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'project' => 'array',
            'published_at' => 'datetime',
        ];
    }
}