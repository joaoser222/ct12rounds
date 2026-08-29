<?php

namespace Tests\Feature;

use App\Models\Modality;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModalityStoreTest extends TestCase
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

    public function test_creation_with_color(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.create');

        $response = $this->actingAs($user)->post(route('modalities.store'), [
            'name' => 'Pilates',
            'color' => '#FF5733',
        ]);

        $response->assertRedirect(route('modalities.index'));

        $modality = Modality::query()->where('name', 'Pilates')->firstOrFail();
        $this->assertSame('#FF5733', $modality->color);
    }

    public function test_color_must_be_hex(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.create');

        $response = $this->actingAs($user)->post(route('modalities.store'), [
            'name' => 'Pilates',
            'color' => 'not-a-color',
        ]);
        $response->assertSessionHasErrors(['color']);
    }

    public function test_update_updates_name_and_color(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.update');

        $modality = Modality::query()->create([
            'name' => 'Pilates',
            'visibility' => 'visible',
        ]);

        $response = $this->actingAs($user)->put(route('modalities.update', $modality->id), [
            'name' => 'Pilates Avançado',
            'color' => '#00AAFF',
        ]);

        $response->assertRedirect(route('modalities.index'));

        $modality->refresh();
        $this->assertSame('Pilates Avançado', $modality->name);
        $this->assertSame('#00AAFF', $modality->color);
    }
}