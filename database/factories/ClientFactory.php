<?php

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Enums\GenderType;
use App\Enums\Visibility;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'document' => fake()->numerify('###########'),
            'birth_date' => fake()->date(),
            'phone' => fake()->numerify('###########'),
            'gender' => fake()->randomElement(GenderType::cases())->value,
            'address' => fake()->streetName(),
            'address_number' => (string) fake()->buildingNumber(),
            'address_complement' => fake()->optional()->secondaryAddress(),
            'address_state' => fake()->stateAbbr(),
            'address_city' => fake()->city(),
            'address_district' => fake()->citySuffix(),
            'address_postal_code' => fake()->numerify('########'),
            'audience_category' => 'adult',
            'status' => ClientStatus::ACTIVE->value,
            'visibility' => Visibility::VISIBLE->value,
        ];
    }

    public function testClient(): static
    {
        return $this->state([
            'name' => 'Cliente Teste',
            'email' => 'cliente@example.com',
            'document' => '12345678901',
        ]);
    }
}
