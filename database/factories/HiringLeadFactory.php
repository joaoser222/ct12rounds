<?php

namespace Database\Factories;

use App\Enums\HiringLeadSource;
use App\Enums\HiringLeadStatus;
use App\Enums\Visibility;
use App\Models\HiringLead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HiringLead>
 */
class HiringLeadFactory extends Factory
{
    protected $model = HiringLead::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('###########'),
            'document' => fake()->numerify('###########'),
            'status' => HiringLeadStatus::NEW->value,
            'source' => HiringLeadSource::SITE->value,
            'visibility' => Visibility::VISIBLE->value,
        ];
    }

    public function contractFlow(): static
    {
        return $this->state([
            'source' => HiringLeadSource::CONTRACT->value,
            'gender' => 'M',
            'birth_date' => fake()->date(),
            'address' => fake()->streetName(),
            'address_number' => (string) fake()->buildingNumber(),
            'address_complement' => fake()->optional()->secondaryAddress(),
            'address_state' => fake()->stateAbbr(),
            'address_city' => fake()->city(),
            'address_district' => fake()->citySuffix(),
            'address_postal_code' => fake()->numerify('########'),
        ]);
    }
}
