<?php

namespace Tests\Feature;

use App\Enums\BillableStatus;
use App\Enums\FinancialAccountType;
use App\Enums\GenderType;
use App\Enums\OperationType;
use App\Models\Client;
use App\Models\Contract;
use App\Models\CostCenter;
use App\Models\Coupon;
use App\Models\DirectLesson;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\Setting;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ModuleDetailsRouteTest extends TestCase
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

    public function test_authenticated_users_can_visit_cost_center_details(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'cost_centers.view');

        $costCenter = CostCenter::query()->create([
            'name' => 'Centro Principal',
            'color' => '#123456',
            'operation_type' => OperationType::PAYABLE->value,
            'visibility' => 'visible',
        ]);

        $response = $this->actingAs($user)->get(route('cost-centers.show', $costCenter));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('cost_centers/Details')
            ->where('cost-center.id', $costCenter->id)
            ->where('cost-center.name', 'Centro Principal')
        );
    }

    public function test_authenticated_users_can_visit_financial_category_details(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'financial_categories.view');

        $costCenter = CostCenter::query()->create([
            'name' => 'Centro Financeiro',
            'color' => '#654321',
            'operation_type' => OperationType::RECEIVABLE->value,
            'visibility' => 'visible',
        ]);

        $financialCategory = FinancialCategory::query()->create([
            'name' => 'Mensalidades',
            'color' => '#abcdef',
            'operation_type' => OperationType::RECEIVABLE->value,
            'cost_center_id' => $costCenter->id,
            'visibility' => 'visible',
        ]);

        $response = $this->actingAs($user)->get(route('financial-categories.show', $financialCategory));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('financial_categories/Details')
            ->where('financial-category.id', $financialCategory->id)
            ->where('financial-category.name', 'Mensalidades')
        );
    }

    public function test_authenticated_users_can_visit_direct_lesson_details(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'direct_lessons.view');

        $client = Client::factory()->create();

        $trainer = Trainer::query()->create([
            'name' => 'Treinador Teste',
            'email' => 'treinador@example.com',
            'document' => '12345678901',
            'birth_date' => '1990-01-01',
            'phone' => '11999999999',
            'gender' => GenderType::MALE->value,
            'visibility' => 'visible',
        ]);

        $directLesson = DirectLesson::query()->create([
            'lesson_date' => '2026-06-27',
            'status' => BillableStatus::OPEN->value,
            'visibility' => 'visible',
            'price' => 120.50,
            'client_id' => $client->id,
            'trainer_id' => $trainer->id,
        ]);

        $response = $this->actingAs($user)->get(route('direct-lessons.show', $directLesson));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('direct_lessons/Details')
            ->where('direct-lesson.id', $directLesson->id)
        );
    }

    public function test_authenticated_users_can_visit_financial_account_details(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'financial_accounts.view');

        $financialAccount = FinancialAccount::query()->create([
            'name' => 'Caixa Principal',
            'account_type' => FinancialAccountType::CASH->value,
            'balance' => 1000,
            'visibility' => 'visible',
        ]);

        $response = $this->actingAs($user)->get(route('financial-accounts.show', $financialAccount));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('financial_accounts/Details')
            ->where('financial-account.id', $financialAccount->id)
            ->where('financial-account.name', 'Caixa Principal')
        );
    }

    public function test_authenticated_users_can_visit_plan_category_details(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'plan_categories.view');

        $planCategory = PlanCategory::query()->create([
            'name' => 'Premium',
            'visibility' => 'visible',
        ]);

        $response = $this->actingAs($user)->get(route('plan-categories.show', $planCategory));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('plan_categories/Details')
            ->where('plan-category.id', $planCategory->id)
            ->where('plan-category.name', 'Premium')
        );
    }

    public function test_authenticated_users_can_visit_contract_details_with_cancel_route(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'contracts.view');
        $this->grantPermission($user, 'contracts.cancel');

        $client = Client::factory()->create();
        $planCategory = PlanCategory::query()->create([
            'name' => 'Premium',
            'visibility' => 'visible',
        ]);
        $plan = Plan::query()->create([
            'name' => 'Plano Teste',
            'modality_quantity' => 1,
            'plan_category_id' => $planCategory->id,
            'visibility' => 'visible',
        ]);
        $coupon = Coupon::query()->create([
            'code' => 'DETALHE10',
            'percent' => 10,
            'discount_limit' => 10,
            'duration' => 1,
            'expiration_date' => '2026-12-31',
            'visibility' => 'visible',
        ]);

        $contract = Contract::query()->create([
            'plan_name' => 'Plano Teste',
            'gross_value' => 120,
            'discount_value' => 10,
            'total' => 110,
            'payment_method' => 'cash',
            'first_due_date' => '2026-07-10',
            'installments' => 1,
            'accepted_terms' => 'accepted',
            'annotations' => 'Observacao do contrato',
            'visibility' => 'visible',
            'status' => BillableStatus::OPEN->value,
            'coupon_id' => $coupon->id,
            'plan_id' => $plan->id,
            'client_id' => $client->id,
        ]);

        $response = $this->actingAs($user)->get(route('contracts.show', $contract));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('contracts/Details')
            ->where('contract.id', $contract->id)
            ->where('contract.gross_value', 120)
            ->where('contract.discount_value', 10)
            ->where('contract.total', 110)
            ->where('contract.payment_method', 'cash')
            ->where('contract.coupon_id', $coupon->id)
            ->where('clientInfo', $client->name.' - '.$client->document)
            ->where('couponInfo', $coupon->code)
            ->has('options.billableStatus', 4)
            ->has('options.paymentMethods', 4)
            ->where('cancelRoute', route('contracts.cancel', $contract))
        );
    }

    public function test_authenticated_users_can_visit_the_custom_settings_page(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'settings.view');
        $this->grantPermission($user, 'settings.update');

        Setting::query()->create([
            'name' => 'contract_default_category',
            'label' => 'Categoria de Contratos',
            'content' => 'Mensalidades',
            'object_type' => 'string',
        ]);

        Setting::query()->create([
            'name' => 'sale_default_category',
            'label' => 'Categoria de Vendas',
            'content' => 'Produtos',
            'object_type' => 'string',
        ]);

        $response = $this->actingAs($user)->get(route('settings.show'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('settings/Details')
            ->where('routes.update', route('settings.update'))
            ->has('settings', 2)
            ->where('settings.0.name', 'contract_default_category')
            ->where('settings.0.label', 'Categoria de Contratos')
            ->where('settings.0.content', 'Mensalidades')
            ->where('settings.0.input_type', 'string')
            ->where('settings.0.select_object_name', null)
            ->where('settings.1.name', 'sale_default_category')
            ->where('settings.1.label', 'Categoria de Vendas')
            ->where('settings.1.content', 'Produtos')
        );
    }

    public function test_authenticated_users_can_update_settings_in_a_single_request(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'settings.update');

        $contractSetting = Setting::query()->create([
            'name' => 'contract_default_category',
            'label' => 'Categoria de Contratos',
            'content' => '',
            'object_type' => 'string',
        ]);

        $saleSetting = Setting::query()->create([
            'name' => 'sale_default_category',
            'label' => 'Categoria de Vendas',
            'content' => '',
            'object_type' => 'string',
        ]);

        $response = $this->actingAs($user)->put(route('settings.update'), [
            'settings' => [
                'contract_default_category' => 'Mensalidades',
                'sale_default_category' => 'Produtos',
            ],
        ]);

        $response->assertRedirect(route('settings.show'));

        $this->assertSame('Mensalidades', $contractSetting->refresh()->content);

        $this->assertSame('Produtos', $saleSetting->refresh()->content);
    }

    public function test_authenticated_users_can_update_select_settings_with_existing_records(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'settings.update');

        $category = FinancialCategory::query()->create([
            'name' => 'Mensalidades',
            'color' => '#000000',
            'operation_type' => OperationType::RECEIVABLE,
        ]);

        $setting = Setting::query()->create([
            'name' => 'contract_default_category',
            'label' => 'Categoria de Contratos',
            'content' => '',
            'object_type' => 'select:financial-category',
        ]);

        $response = $this->actingAs($user)->put(route('settings.update'), [
            'settings' => [
                'contract_default_category' => $category->id,
            ],
        ]);

        $response->assertRedirect(route('settings.show'));

        $this->assertSame($category->id, $setting->refresh()->content);
    }

    public function test_select_settings_reject_missing_records(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'settings.update');

        Setting::query()->create([
            'name' => 'contract_default_category',
            'label' => 'Categoria de Contratos',
            'content' => '',
            'object_type' => 'select:financial-category',
        ]);

        $response = $this->actingAs($user)->putJson(route('settings.update'), [
            'settings' => [
                'contract_default_category' => 999,
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['settings.contract_default_category']);
    }
}
