<?php

namespace Tests\Feature;

use App\Models\LoyaltyLevel;
use Database\Seeders\LoyaltyLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyLevelSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_loyalty_level_seeder_creates_the_expected_records_and_is_idempotent(): void
    {
        $this->seed(LoyaltyLevelSeeder::class);
        $this->seed(LoyaltyLevelSeeder::class);

        $this->assertDatabaseCount('loyalty_levels', 7);

        $this->assertDatabaseHas('loyalty_levels', ['name' => 'Novato', 'min_months' => 0]);
        $this->assertDatabaseHas('loyalty_levels', ['name' => 'Guarda', 'min_months' => 6]);
        $this->assertDatabaseHas('loyalty_levels', ['name' => 'Contendor', 'min_months' => 12]);
        $this->assertDatabaseHas('loyalty_levels', ['name' => 'Peso Médio', 'min_months' => 18]);
        $this->assertDatabaseHas('loyalty_levels', ['name' => 'Peso Pesado', 'min_months' => 24]);
        $this->assertDatabaseHas('loyalty_levels', ['name' => 'Campeão', 'min_months' => 30]);
        $this->assertDatabaseHas('loyalty_levels', ['name' => 'Lenda do Ringue', 'min_months' => 36]);
    }

    public function test_loyalty_level_seeder_preserves_existing_records(): void
    {
        LoyaltyLevel::query()->create([
            'name' => 'Guarda',
            'min_months' => 10,
            'color' => '#ff0000',
        ]);

        $this->seed(LoyaltyLevelSeeder::class);

        $this->assertDatabaseCount('loyalty_levels', 7);
        $this->assertDatabaseHas('loyalty_levels', ['name' => 'Guarda', 'min_months' => 6]);
    }
}
