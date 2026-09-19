<?php

namespace Tests\Feature\Reports;

use App\Models\Client;
use App\Models\Permission;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\ReportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MonthlyBirthdaysReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_seeder_registers_catalog_entry(): void
    {
        $this->seed(ReportSeeder::class);

        $this->assertDatabaseHas('reports', [
            'name' => 'monthly_birthdays',
            'label' => 'Aniversariantes do mês',
        ]);
    }

    public function test_authorized_user_can_run_monthly_birthdays_report(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'reports.view');

        $this->seed(ReportSeeder::class);
        $report = Report::query()->where('name', 'monthly_birthdays')->firstOrFail();

        Client::factory()->create(['name' => 'Aniversariante Junho', 'birth_date' => '1990-06-20']);
        Client::factory()->create(['name' => 'Aniversariante Julho', 'birth_date' => '1990-07-10']);

        $response = $this->actingAs($user)->get(route('reports.run', [
            'report' => $report,
            'month' => 6,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('reports/Run')
            ->where('definition.key', 'monthly_birthdays')
            ->has('rows', 1)
            ->where('rows.0.name', 'Aniversariante Junho')
            ->where('rows.0.birth_day', 20)
            ->where('filters.month', 6)
        );
    }

    public function test_report_does_not_execute_when_opened_without_filters(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'reports.view');

        $this->seed(ReportSeeder::class);
        $report = Report::query()->where('name', 'monthly_birthdays')->firstOrFail();

        Client::factory()->create(['name' => 'Aniversariante Atual', 'birth_date' => '1990-06-01']);

        $response = $this->actingAs($user)->get(route('reports.run', ['report' => $report]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('reports/Run')
            ->where('filters', [])
            ->where('rows', [])
        );
    }

    public function test_running_an_unregistered_report_returns_not_found(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'reports.view');

        $report = Report::query()->create([
            'name' => 'unknown_report',
            'label' => 'Desconhecido',
            'description' => 'Sem definição registrada',
        ]);

        $this->actingAs($user)
            ->get(route('reports.run', ['report' => $report]))
            ->assertNotFound();
    }

    public function test_user_without_permission_cannot_run_report(): void
    {
        $this->seed(ReportSeeder::class);
        $report = Report::query()->where('name', 'monthly_birthdays')->firstOrFail();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.run', ['report' => $report]))
            ->assertForbidden();
    }

    protected function grantPermission(User $user, string $permissionName): void
    {
        $permission = Permission::query()->create([
            'name' => $permissionName,
            'description' => $permissionName,
        ]);

        $user->permissions()->attach($permission);
    }
}
