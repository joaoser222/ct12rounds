<?php

namespace Tests\Feature;

use App\Models\Modality;
use App\Models\Permission;
use App\Models\Trainer;
use App\Models\TrainerModality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function grantPermission(User $user, string $permission): void
    {
        $permission = Permission::query()->create([
            'name' => $permission,
            'description' => $permission,
        ]);

        $user->permissions()->attach($permission);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Treinador Teste',
            'document' => '12345678901',
            'phone' => '11999999999',
            'gender' => 'male',
            'address_postal_code' => '01001000',
            'address_number' => '100',
            'address' => 'Rua Teste',
            'address_district' => 'Centro',
            'address_city' => 'São Paulo',
            'address_state' => 'SP',
        ];
    }

    public function test_trainer_creation_requires_cep_and_number(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'trainers.create');

        $withoutCep = $this->validPayload();
        unset($withoutCep['address_postal_code']);
        $response = $this->actingAs($user)->post(route('trainers.store'), $withoutCep);
        $response->assertSessionHasErrors(['address_postal_code']);

        $withoutNumber = $this->validPayload();
        unset($withoutNumber['address_number']);
        $response = $this->actingAs($user)->post(route('trainers.store'), $withoutNumber);
        $response->assertSessionHasErrors(['address_number']);
    }

    public function test_trainer_creation_allows_optional_address_fields(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'trainers.create');

        $payload = $this->validPayload();
        unset($payload['address'], $payload['address_district'], $payload['address_city'], $payload['address_state']);

        $response = $this->actingAs($user)->post(route('trainers.store'), $payload);

        $response->assertRedirect(route('trainers.index'));
        $this->assertDatabaseHas('trainers', ['document' => '12345678901']);
    }

    public function test_trainer_creation_persists_linked_modalities(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'trainers.create');

        $boxe = Modality::query()->create(['name' => 'Boxe']);
        $jiu = Modality::query()->create(['name' => 'Jiu-Jitsu']);

        $response = $this->actingAs($user)->post(route('trainers.store'), [
            ...$this->validPayload(),
            'trainer_modalities' => [$boxe->id, $jiu->id],
        ]);

        $response->assertRedirect(route('trainers.index'));

        $trainer = Trainer::query()->with('modalities')->firstOrFail();
        $this->assertCount(2, $trainer->modalities);
        $this->assertSame([$boxe->id, $jiu->id], $trainer->modalities->pluck('modality_id')->all());
    }

    public function test_trainer_creation_rejects_invalid_modality_ids(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'trainers.create');

        $response = $this->actingAs($user)->post(route('trainers.store'), [
            ...$this->validPayload(),
            'trainer_modalities' => [999999],
        ]);

        $response->assertSessionHasErrors(['trainer_modalities.0']);
        $this->assertDatabaseCount('trainers', 0);
    }

    public function test_trainer_update_can_clear_all_linked_modalities(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'trainers.update');

        $boxe = Modality::query()->create(['name' => 'Boxe']);
        $jiu = Modality::query()->create(['name' => 'Jiu-Jitsu']);

        $trainer = Trainer::query()->create([
            ...$this->validPayload(),
            'visibility' => 'visible',
        ]);

        TrainerModality::query()->create([
            'trainer_id' => $trainer->id,
            'modality_id' => $boxe->id,
        ]);
        TrainerModality::query()->create([
            'trainer_id' => $trainer->id,
            'modality_id' => $jiu->id,
        ]);

        $response = $this->actingAs($user)->put(route('trainers.update', $trainer->id), [
            ...$this->validPayload(),
            'trainer_modalities' => [],
        ]);

        $response->assertRedirect(route('trainers.index'));

        $trainer->refresh();
        $this->assertSame('Treinador Teste', $trainer->name);
        $this->assertDatabaseCount('trainer_modalities', 0);
    }
}
