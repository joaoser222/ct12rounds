<?php

namespace Tests\Feature;

use App\Models\Modality;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_creation_with_color_and_icon(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.create');

        $response = $this->actingAs($user)->post(route('modalities.store'), [
            'name' => 'Pilates',
            'color' => '#FF5733',
            'icon' => UploadedFile::fake()->create('pilates.png', 100, 'image/png'),
        ]);

        $response->assertRedirect(route('modalities.index'));

        $modality = Modality::query()->where('name', 'Pilates')->firstOrFail();
        $this->assertSame('#FF5733', $modality->color);
        $this->assertNotNull($modality->icon);
        Storage::disk('public')->assertExists($modality->icon);
        $this->assertNotNull($modality->icon_url);
    }

    public function test_icon_is_stored_with_md5_filename_and_original_extension(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.create');

        $icon = UploadedFile::fake()->create('pilates.jpg', 100, 'image/jpeg');

        $this->actingAs($user)->post(route('modalities.store'), [
            'name' => 'Pilates',
            'color' => '#FF5733',
            'icon' => $icon,
        ]);

        $modality = Modality::query()->where('name', 'Pilates')->firstOrFail();
        $this->assertMatchesRegularExpression(
            '/^modalities\/[a-f0-9]{32}\.jpg$/',
            $modality->icon
        );
        Storage::disk('public')->assertExists($modality->icon);
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

    public function test_icon_must_be_png_or_jpg(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.create');

        $response = $this->actingAs($user)->post(route('modalities.store'), [
            'name' => 'Pilates',
            'icon' => UploadedFile::fake()->create('pilates.gif', 100, 'image/gif'),
        ]);
        $response->assertSessionHasErrors(['icon']);
    }

    public function test_update_replaces_icon_and_deletes_old_one(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.update');

        $oldIcon = UploadedFile::fake()->create('old.png', 100, 'image/png');
        $modality = Modality::query()->create([
            'name' => 'Pilates',
            'visibility' => 'visible',
            'icon' => 'modalities/'.md5('old').'.png',
        ]);
        Storage::disk('public')->put('modalities/'.md5('old').'.png', 'old-content');

        $newIcon = UploadedFile::fake()->create('new.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($user)->put(route('modalities.update', $modality->id), [
            'name' => 'Pilates',
            'color' => '#00AAFF',
            'icon' => $newIcon,
        ]);

        $response->assertRedirect(route('modalities.index'));

        $modality->refresh();
        $this->assertSame('#00AAFF', $modality->color);
        Storage::disk('public')->assertMissing('modalities/'.md5('old').'.png');
        Storage::disk('public')->assertExists($modality->icon);
    }

    public function test_update_removes_icon_when_remove_icon_flag_is_set(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.update');

        $modality = Modality::query()->create([
            'name' => 'Pilates',
            'visibility' => 'visible',
            'icon' => 'modalities/'.md5('remove').'.png',
        ]);
        Storage::disk('public')->put('modalities/'.md5('remove').'.png', 'old-content');

        $response = $this->actingAs($user)->put(route('modalities.update', $modality->id), [
            'name' => 'Pilates',
            'remove_icon' => true,
        ]);

        $response->assertRedirect(route('modalities.index'));

        $modality->refresh();
        $this->assertNull($modality->icon);
        Storage::disk('public')->assertMissing('modalities/'.md5('remove').'.png');
    }

    public function test_update_keeps_icon_when_no_file_and_no_remove_flag(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.update');

        $modality = Modality::query()->create([
            'name' => 'Pilates',
            'visibility' => 'visible',
            'icon' => 'modalities/keep.png',
        ]);
        Storage::disk('public')->put('modalities/keep.png', 'content');

        $response = $this->actingAs($user)->put(route('modalities.update', $modality->id), [
            'name' => 'Pilates Alterado',
        ]);

        $response->assertRedirect(route('modalities.index'));

        $modality->refresh();
        $this->assertSame('Pilates Alterado', $modality->name);
        $this->assertSame('modalities/keep.png', $modality->icon);
        Storage::disk('public')->assertExists('modalities/keep.png');
    }
}
