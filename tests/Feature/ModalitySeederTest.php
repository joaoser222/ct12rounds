<?php

namespace Tests\Feature;

use App\Models\Modality;
use Database\Seeders\ModalitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModalitySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_modality_seeder_creates_the_expected_records_with_color(): void
    {
        $this->seed(ModalitySeeder::class);

        $this->assertDatabaseCount('modalities', 4);

        $expected = [
            'Boxe' => '#DC2626',
            'Jiu-jitsu' => '#0D9488',
            'Kickboxing' => '#EA580C',
            'MMA' => '#2563EB',
        ];

        foreach ($expected as $name => $color) {
            $modality = Modality::query()->where('name', $name)->first();
            $this->assertNotNull($modality);
            $this->assertSame($color, $modality->color);
        }
    }

    public function test_modality_seeder_is_idempotent(): void
    {
        $this->seed(ModalitySeeder::class);
        $this->seed(ModalitySeeder::class);

        $this->assertDatabaseCount('modalities', 4);
    }
}