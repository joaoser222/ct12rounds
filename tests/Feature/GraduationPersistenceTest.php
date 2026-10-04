<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Modality;
use App\Models\ModalityGraduation;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GraduationPersistenceTest extends TestCase
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

    private function graduation(string $name, string $modalityName = 'Kickboxing'): ModalityGraduation
    {
        return ModalityGraduation::query()->create([
            'modality_id' => Modality::factory()->create(['name' => $modalityName])->id,
            'name' => $name,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validClientPayload(): array
    {
        return [
            'name' => 'Cliente Teste',
            'email' => 'cliente@teste.com',
            'phone' => '11999999999',
            'document' => '12345678909',
            'gender' => 'M',
            'birth_date' => '1990-01-01',
            'address_postal_code' => '01001000',
            'address_number' => '100',
            'address_state' => 'SP',
            'address_city' => 'São Paulo',
        ];
    }

    public function test_modality_stores_its_graduations(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.create');

        $response = $this->actingAs($user)->post(route('modalities.store'), [
            'name' => 'Kickboxing',
            'color' => '#FF5733',
            'graduations' => [
                ['name' => 'Faixa Branca'],
                ['name' => 'Faixa Azul'],
            ],
        ]);

        $response->assertRedirect(route('modalities.index'));

        $modality = Modality::query()->where('name', 'Kickboxing')->firstOrFail();

        $this->assertDatabaseCount('modality_graduations', 2);
        $this->assertDatabaseHas('modality_graduations', [
            'modality_id' => $modality->id,
            'name' => 'Faixa Branca',
        ]);
    }

    public function test_updating_a_modality_replaces_its_graduations(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.update');

        $modality = Modality::factory()->create();
        $modality->graduations()->create(['name' => 'Faixa Amarela']);

        $this->actingAs($user)->put(route('modalities.update', $modality->id), [
            'name' => $modality->name,
            'graduations' => [['name' => 'Faixa Preta']],
        ])->assertRedirect(route('modalities.index'));

        $this->assertDatabaseMissing('modality_graduations', ['name' => 'Faixa Amarela']);
        $this->assertDatabaseHas('modality_graduations', [
            'modality_id' => $modality->id,
            'name' => 'Faixa Preta',
        ]);
    }

    public function test_duplicated_graduations_are_stored_once(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.create');

        $this->actingAs($user)->post(route('modalities.store'), [
            'name' => 'Muay Thai',
            'graduations' => [
                ['name' => 'Faixa Branca'],
                ['name' => 'Faixa Branca'],
            ],
        ])->assertRedirect(route('modalities.index'));

        $this->assertDatabaseCount('modality_graduations', 1);
    }

    public function test_graduation_name_is_required(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.create');

        $this->actingAs($user)->post(route('modalities.store'), [
            'name' => 'Muay Thai',
            'graduations' => [['name' => '   ']],
        ])->assertSessionHasErrors('graduations.0.name');

        $this->assertDatabaseCount('modality_graduations', 0);
    }

    public function test_graduation_stores_its_color(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.create');

        $this->actingAs($user)->post(route('modalities.store'), [
            'name' => 'Kickboxing',
            'graduations' => [
                ['name' => 'Faixa Branca', 'color' => '#FFFFFF'],
                ['name' => 'Faixa Azul'],
            ],
        ])->assertRedirect(route('modalities.index'));

        $modality = Modality::query()->where('name', 'Kickboxing')->firstOrFail();

        $this->assertSame(
            ['#ffffff', null],
            $modality->graduations()->orderBy('position')->pluck('color')->all(),
        );
    }

    public function test_graduation_rejects_an_invalid_color(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.create');

        $this->actingAs($user)->post(route('modalities.store'), [
            'name' => 'Kickboxing',
            'graduations' => [['name' => 'Faixa Branca', 'color' => 'azul']],
        ])->assertSessionHasErrors('graduations.0.color');

        $this->assertDatabaseCount('modality_graduations', 0);
    }

    public function test_graduation_name_is_stored_capitalized(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.create');

        $this->actingAs($user)->post(route('modalities.store'), [
            'name' => 'Muay Thai',
            'graduations' => [['name' => '  faixa branca  ']],
        ])->assertRedirect(route('modalities.index'));

        $this->assertDatabaseHas('modality_graduations', ['name' => 'Faixa Branca']);
    }

    public function test_graduations_differing_only_by_case_are_stored_once(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.create');

        $this->actingAs($user)->post(route('modalities.store'), [
            'name' => 'Muay Thai',
            'graduations' => [
                ['name' => 'faixa branca', 'color' => '#111111'],
                ['name' => 'Faixa Branca', 'color' => '#222222'],
            ],
        ])->assertRedirect(route('modalities.index'));

        $this->assertDatabaseCount('modality_graduations', 1);
        $this->assertDatabaseHas('modality_graduations', [
            'name' => 'Faixa Branca',
            'color' => '#111111',
        ]);
    }

    public function test_client_links_a_graduation_of_its_modality(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'clients.create');

        $graduation = $this->graduation('Faixa Preta');

        $this->actingAs($user)->post(route('clients.store'), [
            ...$this->validClientPayload(),
            'graduations' => [
                [
                    'modality_id' => $graduation->modality_id,
                    'modality_graduation_id' => $graduation->id,
                ],
            ],
        ])->assertRedirect(route('clients.index'));

        $client = Client::query()->where('email', 'cliente@teste.com')->firstOrFail();

        $this->assertDatabaseHas('client_graduations', [
            'client_id' => $client->id,
            'modality_graduation_id' => $graduation->id,
        ]);
    }

    public function test_client_rejects_a_graduation_from_another_modality(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'clients.create');

        $graduation = $this->graduation('Faixa Preta', 'Kickboxing');
        $otherModality = Modality::factory()->create(['name' => 'Muay Thai']);

        $response = $this->actingAs($user)->post(route('clients.store'), [
            ...$this->validClientPayload(),
            'graduations' => [
                [
                    'modality_id' => $otherModality->id,
                    'modality_graduation_id' => $graduation->id,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('graduations.0.modality_graduation_id');
        $this->assertDatabaseCount('client_graduations', 0);
    }

    public function test_updating_a_client_replaces_its_graduations(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'clients.update');

        $client = Client::factory()->create();
        $old = $this->graduation('Faixa Branca');
        $new = $this->graduation('Faixa Roxa');

        $client->graduations()->create([
            'modality_graduation_id' => $old->id,
        ]);

        $this->actingAs($user)->put(route('clients.update', $client->id), [
            ...$this->validClientPayload(),
            'graduations' => [
                [
                    'modality_id' => $new->modality_id,
                    'modality_graduation_id' => $new->id,
                ],
            ],
        ])->assertRedirect(route('clients.index'));

        $this->assertDatabaseMissing('client_graduations', [
            'client_id' => $client->id,
            'modality_graduation_id' => $old->id,
        ]);
        $this->assertDatabaseHas('client_graduations', [
            'client_id' => $client->id,
            'modality_graduation_id' => $new->id,
        ]);
    }

    public function test_deleting_a_modality_removes_its_graduations(): void
    {
        $modality = Modality::factory()->create();
        $graduation = ModalityGraduation::query()->create([
            'modality_id' => $modality->id,
            'name' => 'Faixa Preta',
        ]);

        $modality->delete();

        $this->assertDatabaseMissing('modality_graduations', ['id' => $graduation->id]);
    }

    public function test_editing_a_modality_keeps_the_graduations_linked_to_clients(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.update');

        $modality = Modality::factory()->create();
        $kept = $modality->graduations()->create(['name' => 'Faixa Branca']);
        $dropped = $modality->graduations()->create(['name' => 'Faixa Azul']);

        $client = Client::factory()->create();
        $client->graduations()->create(['modality_graduation_id' => $kept->id]);
        $client->graduations()->create(['modality_graduation_id' => $dropped->id]);

        $this->actingAs($user)->put(route('modalities.update', $modality->id), [
            'name' => $modality->name,
            'graduations' => [
                ['id' => $kept->id, 'name' => 'Faixa Branca renamed'],
            ],
        ])->assertRedirect(route('modalities.index'));

        $this->assertDatabaseHas('client_graduations', [
            'client_id' => $client->id,
            'modality_graduation_id' => $kept->id,
        ]);
        $this->assertDatabaseMissing('client_graduations', [
            'client_id' => $client->id,
            'modality_graduation_id' => $dropped->id,
        ]);
    }

    public function test_a_graduation_id_from_another_modality_is_not_reused(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.update');

        $modality = Modality::factory()->create(['name' => 'Kickboxing']);
        $other = Modality::factory()->create(['name' => 'Muay Thai']);

        $foreign = $other->graduations()->create(['name' => 'Faixa Branca']);

        $this->actingAs($user)->put(route('modalities.update', $modality->id), [
            'name' => $modality->name,
            'graduations' => [
                ['id' => $foreign->id, 'name' => 'Faixa Branca'],
            ],
        ])->assertRedirect(route('modalities.index'));

        $this->assertDatabaseHas('modality_graduations', [
            'id' => $foreign->id,
            'modality_id' => $other->id,
        ]);
        $this->assertDatabaseMissing('modality_graduations', [
            'id' => $foreign->id,
            'modality_id' => $modality->id,
        ]);
    }

    public function test_graduations_are_stored_in_the_submitted_order(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.create');

        $this->actingAs($user)->post(route('modalities.store'), [
            'name' => 'Muay Thai',
            'graduations' => [
                ['name' => 'Branca'],
                ['name' => 'Azul'],
                ['name' => 'Vermelha'],
            ],
        ])->assertRedirect(route('modalities.index'));

        $modality = Modality::query()->where('name', 'Muay Thai')->firstOrFail();

        $this->assertSame(
            ['Branca', 'Azul', 'Vermelha'],
            $modality->graduations()->orderBy('position')->pluck('name')->all(),
        );
    }

    public function test_reordering_graduations_does_not_rename_them(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.update');
        $this->grantPermission($user, 'modalities.view');

        $modality = Modality::factory()->create(['name' => 'Muay Thai']);
        $white = $modality->graduations()->create(['name' => 'Branca', 'position' => 0]);
        $blue = $modality->graduations()->create(['name' => 'Azul', 'position' => 1]);
        $red = $modality->graduations()->create(['name' => 'Vermelha', 'position' => 2]);

        $this->actingAs($user)->put(route('modalities.update', $modality->id), [
            'name' => $modality->name,
            'graduations' => [
                ['id' => $blue->id, 'name' => 'Azul'],
                ['id' => $white->id, 'name' => 'Branca'],
                ['id' => $red->id, 'name' => 'Vermelha'],
            ],
        ])->assertRedirect(route('modalities.index'));

        $this->assertSame(
            ['Azul', 'Branca', 'Vermelha'],
            $modality->graduations()->orderBy('position')->pluck('name')->all(),
        );
    }

    public function test_modality_details_exposes_graduations_in_the_reordered_position(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.update');
        $this->grantPermission($user, 'modalities.view');

        $modality = Modality::factory()->create(['name' => 'Muay Thai']);
        $white = $modality->graduations()->create(['name' => 'Branca', 'position' => 0]);
        $blue = $modality->graduations()->create(['name' => 'Azul', 'position' => 1]);

        $this->actingAs($user)->put(route('modalities.update', $modality->id), [
            'name' => $modality->name,
            'graduations' => [
                ['id' => $blue->id, 'name' => 'Azul'],
                ['id' => $white->id, 'name' => 'Branca'],
            ],
        ])->assertRedirect(route('modalities.index'));

        $response = $this->actingAs($user)->get(route('modalities.show', $modality->id));

        $response->assertInertia(fn ($page) => $page
            ->component('modalities/Details')
            ->where('graduations.0.id', $blue->id)
            ->where('graduations.0.name', 'Azul')
            ->where('graduations.1.id', $white->id)
            ->where('graduations.1.name', 'Branca')
        );
    }

    public function test_client_graduations_store_the_promotion_date(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'clients.update');

        $client = Client::factory()->create();
        $first = $this->graduation('Faixa Branca');
        $second = $this->graduation('Faixa Azul');

        $this->actingAs($user)->put(route('clients.update', $client->id), [
            ...$this->validClientPayload(),
            'graduations' => [
                [
                    'modality_id' => $first->modality_id,
                    'modality_graduation_id' => $first->id,
                    'promoted_at' => '2026-01-10',
                ],
                [
                    'modality_id' => $second->modality_id,
                    'modality_graduation_id' => $second->id,
                    'promoted_at' => '2026-06-20',
                ],
            ],
        ])->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('client_graduations', [
            'client_id' => $client->id,
            'modality_graduation_id' => $second->id,
            'promoted_at' => '2026-06-20',
        ]);
    }

    public function test_client_graduations_reject_an_invalid_promotion_date(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'clients.update');

        $client = Client::factory()->create();
        $graduation = $this->graduation('Faixa Branca');

        $this->actingAs($user)->put(route('clients.update', $client->id), [
            ...$this->validClientPayload(),
            'graduations' => [
                [
                    'modality_id' => $graduation->modality_id,
                    'modality_graduation_id' => $graduation->id,
                    'promoted_at' => 'ontem',
                ],
            ],
        ])->assertSessionHasErrors('graduations.0.promoted_at');
    }

    public function test_client_details_exposes_graduations_ordered_by_position(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'clients.view');

        $client = Client::factory()->create();
        $modality = Modality::factory()->create(['visibility' => 'visible']);

        $second = $modality->graduations()->create(['name' => 'Faixa Azul', 'position' => 1]);
        $first = $modality->graduations()->create(['name' => 'Faixa Branca', 'position' => 0]);

        $client->graduations()->create([
            'modality_graduation_id' => $first->id,
            'promoted_at' => '2026-01-10',
        ]);

        $response = $this->actingAs($user)->get(route('clients.show', $client->id));

        $response->assertInertia(fn ($page) => $page
            ->component('clients/Details')
            ->where('graduations.0.modality_graduation_id', $first->id)
            ->where('graduations.0.promoted_at', '2026-01-10')
            ->where('graduationOptions.0.value', (string) $first->id)
            ->where('graduationOptions.0.position', 0)
            ->where('graduationOptions.1.value', (string) $second->id)
            ->where('graduationOptions.1.position', 1)
        );
    }
}
