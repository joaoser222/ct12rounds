<?php

namespace Tests\Feature;

use App\Models\ModalityCategory;
use Database\Seeders\ModalityCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModalityCategorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_modality_category_seeder_creates_the_expected_records_and_is_idempotent(): void
    {
        $this->seed(ModalityCategorySeeder::class);
        $this->seed(ModalityCategorySeeder::class);

        $this->assertDatabaseCount('modality_categories', 2);

        $this->assertDatabaseHas('modality_categories', ['name' => 'Adulto', 'audience' => 'adult']);
        $this->assertDatabaseHas('modality_categories', ['name' => 'Infantil', 'audience' => 'child']);
    }
}
