<?php

namespace Tests\Feature;

use App\Models\Modality;
use Database\Seeders\ModalitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModalitySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_modality_seeder_creates_the_expected_records_with_color_and_icon(): void
    {
        Storage::fake('public');

        $this->seed(ModalitySeeder::class);

        $this->assertDatabaseCount('modalities', 4);

        $expected = [
            'Boxe' => ['#DC2626', 'modalities/boxe.png'],
            'Jiu-jitsu' => ['#0D9488', 'modalities/jiu-jitsu.png'],
            'Kickboxing' => ['#EA580C', 'modalities/kickboxing.png'],
            'MMA' => ['#2563EB', 'modalities/mma.png'],
        ];

        foreach ($expected as $name => [$color, $icon]) {
            $modality = Modality::query()->where('name', $name)->first();
            $this->assertNotNull($modality);
            $this->assertSame($color, $modality->color);
            $this->assertSame($icon, $modality->icon);
            Storage::disk('public')->assertExists($icon);
        }
    }

    public function test_modality_seeder_is_idempotent(): void
    {
        Storage::fake('public');

        $this->seed(ModalitySeeder::class);
        $this->seed(ModalitySeeder::class);

        $this->assertDatabaseCount('modalities', 4);
    }
}
