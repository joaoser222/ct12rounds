<?php

namespace Tests\Feature;

use App\Models\ClassSchedule;
use App\Models\Modality;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClassScheduleModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function grantPermission(User $user, string $permission): void
    {
        $perm = Permission::query()->create([
            'name' => $permission,
            'description' => $permission,
        ]);

        $user->permissions()->attach($perm);
    }

    public function test_guests_are_redirected_from_class_schedules_index(): void
    {
        $response = $this->get(route('class-schedules.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_class_schedules_index(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'class_schedules.view');

        $response = $this->actingAs($user)->get(route('class-schedules.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('class_schedules/Index')
            ->has('class-schedules.data')
            ->has('routes')
        );
    }

    public function test_authenticated_users_can_visit_class_schedule_create(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'class_schedules.create');

        $response = $this->actingAs($user)->get(route('class-schedules.create'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('class_schedules/Details')
            ->where('class-schedule', null)
            ->where('id', 'new')
            ->has('routes')
        );
    }

    public function test_class_schedule_index_requires_view_permission(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('class-schedules.index'));

        $response->assertForbidden();
    }

    public function test_class_schedule_index_lists_modality_name(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'class_schedules.view');
        $modality = Modality::factory()->create(['name' => 'Jiu-Jitsu']);
        ClassSchedule::factory()->create([
            'modality_id' => $modality->id,
            'week_day' => 1,
            'start_time' => '07:00',
            'end_time' => '08:00',
        ]);

        $response = $this->actingAs($user)->get(route('class-schedules.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('class_schedules/Index')
            ->has('class-schedules.data', 1)
            ->where('class-schedules.data.0.modality_name', 'Jiu-Jitsu')
        );
    }

    public function test_users_can_create_class_schedule(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'class_schedules.create');
        $modality = Modality::factory()->create();

        $response = $this->actingAs($user)->post(route('class-schedules.store'), [
            'modality_id' => $modality->id,
            'week_day' => 1,
            'start_time' => '07:00',
            'end_time' => '08:00',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('class_schedules', [
            'modality_id' => $modality->id,
            'week_day' => 1,
            'start_time' => '07:00',
        ]);
    }

    public function test_users_can_update_class_schedule(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'class_schedules.update');
        $modality = Modality::factory()->create();
        $classSchedule = ClassSchedule::factory()->create(['modality_id' => $modality->id]);

        $response = $this->actingAs($user)->put(route('class-schedules.update', $classSchedule), [
            'week_day' => 2,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('class_schedules', [
            'id' => $classSchedule->id,
            'week_day' => 2,
        ]);
    }

    public function test_users_can_delete_class_schedule(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'class_schedules.delete');
        $classSchedule = ClassSchedule::factory()->create();

        $response = $this->actingAs($user)->delete(route('class-schedules.destroy'), [
            'items' => [$classSchedule->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('class_schedules', ['id' => $classSchedule->id]);
    }
}
