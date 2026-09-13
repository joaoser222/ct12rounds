<?php

namespace Database\Factories;

use App\Models\LandingContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LandingContent>
 */
class LandingContentFactory extends Factory
{
    protected $model = LandingContent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project' => [],
            'draft_html' => '<p>Landing em rascunho</p>',
            'published_html' => null,
            'status' => LandingContent::STATUS_DRAFT,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'published_html' => $attributes['draft_html'] ?? '<p>Landing publicada</p>',
            'status' => LandingContent::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }
}