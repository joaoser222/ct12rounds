<?php

namespace Tests\Feature;

use App\Enums\GenderType;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Coupon;
use App\Models\HiringLead;
use App\Models\Invoice;
use App\Models\Modality;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\PlanModality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class HiringFlowTest extends TestCase
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

    private function createPlan(): Plan
    {
        $planCategory = PlanCategory::query()->create([
            'name' => 'Premium',
            'visibility' => 'visible',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Plano Performance',
            'plan_category_id' => $planCategory->id,
            'description' => 'Plano com múltiplas durações.',
            'price' => 699.9,
            'duration_months' => 12,
            'visibility' => 'visible',
        ]);

        $modality1 = Modality::query()->create(['name' => 'Boxe', 'visibility' => 'visible']);
        $modality2 = Modality::query()->create(['name' => 'Jiu-jitsu', 'visibility' => 'visible']);
        $modality3 = Modality::query()->create(['name' => 'MMA', 'visibility' => 'visible']);

        PlanModality::query()->create(['plan_id' => $plan->id, 'modality_id' => $modality1->id]);
        PlanModality::query()->create(['plan_id' => $plan->id, 'modality_id' => $modality2->id]);
        PlanModality::query()->create(['plan_id' => $plan->id, 'modality_id' => $modality3->id]);

        return $plan;
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(Plan $plan): array
    {
        return [
            'plan_id' => $plan->id,
            'installments' => 12,
            'annotations' => 'Contratação criada pelo wizard.',
        ];
    }

    public function test_users_with_required_permissions_can_open_the_contract_wizard_page(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'contracts.create');
        $plan = $this->createPlan();

        $response = $this->actingAs($user)->get(route('contracts.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('contracts/Details')
            ->where('routes.store', route('contracts.store'))
            ->where('options.plans.0.value', $plan->id)
            ->where('options.plans.0.title', 'Plano Performance')
            ->has('options.coupons')
        );
    }

    public function test_contract_wizard_creates_pending_contract_with_registration_token(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'contracts.create');
        $plan = $this->createPlan();
        $coupon = Coupon::query()->create([
            'code' => 'BEMVINDO',
            'percent' => 10,
            'discount_limit' => 100,
            'duration' => 30,
            'expiration_date' => '2026-12-31',
            'visibility' => 'visible',
        ]);

        $payload = $this->validPayload($plan);
        $payload['coupon_id'] = $coupon->id;

        $response = $this->actingAs($user)->post(route('contracts.store'), $payload);

        $contract = Contract::query()->firstOrFail();

        $response->assertRedirect(route('contracts.show', $contract));

        $this->assertSame('pending', $contract->accepted_terms);
        $this->assertNull($contract->client_id);
        $this->assertNotNull($contract->registration_token);
        $this->assertSame($plan->id, $contract->plan_id);
        $this->assertSame('Plano Performance', $contract->plan_name);
        $this->assertSame($coupon->id, $contract->coupon_id);
        $this->assertSame(12, $contract->installments);
        $this->assertSame(Date::today()->format('Y-m-d'), $contract->first_due_date?->format('Y-m-d'));
        $this->assertEquals(8398.8, $contract->gross_value);
        $this->assertEquals(0.0, $contract->discount_value);
        $this->assertEquals(8398.8, $contract->total);
        $this->assertDatabaseCount('clients', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('contract_modalities', 3);
    }

    public function test_contract_wizard_validates_the_selected_plan_duration_combination(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'contracts.create');
        $plan = $this->createPlan();

        $payload = $this->validPayload($plan);
        $payload['installments'] = 13;

        $response = $this->actingAs($user)->post(route('contracts.store'), $payload);

        $response->assertSessionHasErrors(['installments']);
        $this->assertDatabaseCount('contracts', 0);
    }

    public function test_contract_can_be_applied_after_client_registration(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'contracts.update');
        $plan = $this->createPlan();
        $coupon = Coupon::query()->create([
            'code' => 'BEMVINDO',
            'percent' => 10,
            'discount_limit' => 100,
            'duration' => 30,
            'expiration_date' => '2026-12-31',
            'visibility' => 'visible',
        ]);

        $contract = Contract::query()->create([
            'plan_name' => 'Plano Performance',
            'gross_value' => 8398.8,
            'discount_value' => 0,
            'total' => 8398.8,
            'payment_method' => 'cash',
            'first_due_date' => Date::today()->format('Y-m-d'),
            'installments' => 12,
            'accepted_terms' => 'pending',
            'plan_id' => $plan->id,
            'coupon_id' => $coupon->id,
            'registration_token' => 'qr-token-abc',
            'visibility' => 'visible',
        ]);

        $lead = HiringLead::query()->create([
            'name' => 'Cliente QR',
            'email' => 'qr@example.com',
            'phone' => '11977776666',
            'document' => '22233344455',
            'gender' => GenderType::MALE->value,
            'birth_date' => '1992-05-10',
            'address' => 'Rua QR',
            'address_number' => '10',
            'address_district' => 'Centro',
            'address_state' => 'SP',
            'address_city' => 'Sao Paulo',
            'address_postal_code' => '01001000',
            'status' => 'new',
            'source' => 'contract',
            'accepted_at' => Date::now(),
            'visibility' => 'visible',
            'plan_id' => $plan->id,
            'coupon_id' => $coupon->id,
            'contract_id' => $contract->id,
        ]);

        $response = $this->actingAs($user)->patch(route('contracts.apply', $contract));

        $response->assertRedirect(route('contracts.show', $contract));

        $contract->refresh();

        $this->assertNotNull($contract->client_id);
        $this->assertSame('accepted', $contract->accepted_terms);
        $this->assertEquals(100.0, $contract->discount_value);
        $this->assertEquals(8298.8, $contract->total);
        $this->assertDatabaseCount('invoices', 12);

        $this->assertDatabaseHas('clients', [
            'id' => $contract->client_id,
            'document' => '22233344455',
            'name' => 'Cliente QR',
        ]);

        $this->assertDatabaseHas('hiring_leads', [
            'id' => $lead->id,
            'status' => 'converted',
            'client_id' => $contract->client_id,
        ]);

        $firstInvoice = Invoice::query()->orderBy('installment_number')->firstOrFail();

        $this->assertSame('contract', $firstInvoice->billable_type);
        $this->assertSame($contract->id, $firstInvoice->billable_id);
        $this->assertSame(Date::today()->format('Y-m-d'), $firstInvoice->due_date?->format('Y-m-d'));
        $this->assertEquals(8.3334, $firstInvoice->discount_value);
    }

    public function test_contract_application_requires_a_linked_registration(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'contracts.update');
        $plan = $this->createPlan();

        $contract = Contract::query()->create([
            'plan_name' => 'Plano Performance',
            'gross_value' => 8398.8,
            'discount_value' => 0,
            'total' => 8398.8,
            'payment_method' => 'cash',
            'first_due_date' => Date::today()->format('Y-m-d'),
            'installments' => 12,
            'accepted_terms' => 'pending',
            'plan_id' => $plan->id,
            'registration_token' => 'qr-token-abc',
            'visibility' => 'visible',
        ]);

        $response = $this->actingAs($user)->patch(route('contracts.apply', $contract));

        $response->assertSessionHasErrors('contract');

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'accepted_terms' => 'pending',
            'client_id' => null,
        ]);
        $this->assertDatabaseCount('clients', 0);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_contract_application_is_rejected_when_already_applied(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'contracts.update');
        $plan = $this->createPlan();

        $client = Client::factory()->create();

        $contract = Contract::query()->create([
            'plan_name' => 'Plano Performance',
            'gross_value' => 8398.8,
            'discount_value' => 0,
            'total' => 8398.8,
            'payment_method' => 'cash',
            'first_due_date' => Date::today()->format('Y-m-d'),
            'installments' => 12,
            'accepted_terms' => 'accepted',
            'plan_id' => $plan->id,
            'client_id' => $client->id,
            'registration_token' => 'qr-token-abc',
            'visibility' => 'visible',
        ]);

        $contract->invoices()->create([
            'operation_type' => 'receivable',
            'invoice_type' => 'standard',
            'due_date' => '2026-09-01',
            'payment_method' => 'cash',
            'gross_value' => 699.9,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 0,
            'installment_number' => 1,
            'status' => 'pending',
            'visibility' => 'visible',
            'holder_type' => 'client',
            'holder_id' => $client->id,
        ]);

        $response = $this->actingAs($user)->patch(route('contracts.apply', $contract));

        $response->assertSessionHasErrors('contract');
        $this->assertDatabaseCount('invoices', 1);
        $this->assertSame($client->id, $contract->refresh()->client_id);
    }

    public function test_users_can_cancel_contract_and_non_paid_invoices_with_permission(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'contracts.cancel');

        $client = Client::factory()->create();
        $contract = Contract::query()->create([
            'plan_name' => 'Plano Teste',
            'gross_value' => 300,
            'discount_value' => 0,
            'total' => 300,
            'payment_method' => 'cash',
            'first_due_date' => '2026-07-10',
            'installments' => 3,
            'accepted_terms' => 'accepted',
            'visibility' => 'visible',
            'status' => 'open',
            'client_id' => $client->id,
        ]);

        Invoice::query()->create([
            'operation_type' => 'receivable',
            'invoice_type' => 'standard',
            'due_date' => '2026-07-10',
            'payment_method' => 'cash',
            'gross_value' => 100,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 0,
            'installment_number' => 1,
            'status' => 'pending',
            'visibility' => 'visible',
            'holder_type' => 'client',
            'holder_id' => $client->id,
            'billable_type' => 'contract',
            'billable_id' => $contract->id,
        ]);

        Invoice::query()->create([
            'operation_type' => 'receivable',
            'invoice_type' => 'standard',
            'due_date' => '2026-08-10',
            'payment_date' => '2026-08-10',
            'payment_method' => 'cash',
            'gross_value' => 100,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 100,
            'installment_number' => 2,
            'status' => 'paid',
            'visibility' => 'visible',
            'holder_type' => 'client',
            'holder_id' => $client->id,
            'billable_type' => 'contract',
            'billable_id' => $contract->id,
        ]);

        $response = $this->actingAs($user)->patch(route('contracts.cancel', $contract));

        $response->assertRedirect(route('contracts.index'));
        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'status' => 'canceled',
        ]);
        $this->assertDatabaseHas('invoices', [
            'billable_type' => 'contract',
            'billable_id' => $contract->id,
            'installment_number' => 1,
            'status' => 'canceled',
        ]);
        $this->assertDatabaseHas('invoices', [
            'billable_type' => 'contract',
            'billable_id' => $contract->id,
            'installment_number' => 2,
            'status' => 'paid',
        ]);
    }
}
