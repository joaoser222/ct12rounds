<?php

declare(strict_types=1);

namespace Tests\Feature\HiringLeads;

use App\Enums\HiringLeadSource;
use App\Enums\Visibility;
use App\Models\Contract;
use App\Models\Coupon;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicHiringLeadStoreTest extends TestCase
{
    use RefreshDatabase;

    private array $validCardData = [
        'card_number' => '4111111111111111',
        'card_expiry_month' => '12',
        'card_expiry_year' => '2030',
        'card_cvv' => '123',
        'card_holder_name' => 'MARIA SILVA',
    ];

    public function test_pre_registration_creates_lead_with_site_source(): void
    {
        $this->post('/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'document' => '12345678901',
            'accepted' => true,
        ])->assertRedirect('/register');

        $this->assertDatabaseHas('hiring_leads', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'document' => '12345678901',
            'source' => HiringLeadSource::SITE->value,
        ]);
    }

    public function test_pre_registration_requires_terms_acceptance(): void
    {
        $this->post('/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'document' => '12345678901',
        ])->assertSessionHasErrors('accepted');

        $this->assertDatabaseCount('hiring_leads', 0);
    }

    public function test_contract_registration_requires_full_address(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Mensal',
            'public_slug' => 'mensal',
            'price' => 100,
            'duration_months' => 1,
            'visibility' => Visibility::VISIBLE->value,
        ]);

        $contract = Contract::query()->create([
            'plan_name' => $plan->name,
            'gross_value' => 100,
            'discount_value' => 0,
            'total' => 100,
            'payment_method' => 'cash',
            'first_due_date' => '2026-09-01',
            'installments' => 1,
            'accepted_terms' => 'pending',
            'visibility' => Visibility::VISIBLE->value,
            'plan_id' => $plan->id,
            'registration_token' => 'test-token-address',
        ]);

        $this->post('/register', [
            'contract' => $contract->registration_token,
            'name' => 'Joao Souza',
            'email' => 'joao@example.com',
            'phone' => '11988888888',
            'document' => '99887766554',
            'gender' => 'M',
            'birth_date' => '1990-01-01',
            'accepted' => true,
            'is_contract_flow' => true,
            ...$this->validCardData,
        ])->assertSessionHasErrors(['address', 'address_number', 'address_district', 'address_state', 'address_city', 'address_postal_code']);
    }

    public function test_contract_registration_rejects_invalid_contract_token(): void
    {
        $this->post('/register', [
            'contract' => 'token-inexistente',
            'name' => 'Joao QR',
            'email' => 'joaoqr@example.com',
            'phone' => '11977776666',
            'document' => '33344455566',
            'gender' => 'M',
            'birth_date' => '1990-01-01',
            'address' => 'Rua das Flores',
            'address_number' => '100',
            'address_district' => 'Centro',
            'address_state' => 'SP',
            'address_city' => 'Sao Paulo',
            'address_postal_code' => '01001000',
            'accepted' => true,
            'is_contract_flow' => true,
            ...$this->validCardData,
        ])->assertSessionHasErrors('contract');

        $this->assertDatabaseCount('hiring_leads', 0);
    }

    public function test_registration_page_exposes_contract_registration_by_token(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $this->get('/register?contract='.$contract->registration_token)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/Registration')
                ->where('contract.id', $contract->id)
                ->where('contract.token', $contract->registration_token)
                ->where('plan.id', $plan->id)
            );
    }

    private function createPlanWithContract(string $name, string $slug): Plan
    {
        return Plan::query()->create([
            'name' => $name,
            'public_slug' => $slug,
            'price' => 100,
            'duration_months' => 1,
            'visibility' => Visibility::VISIBLE->value,
        ]);
    }

    private function createPendingContract(Plan $plan, ?Coupon $coupon = null): Contract
    {
        return Contract::query()->create([
            'plan_name' => $plan->name,
            'gross_value' => 100,
            'discount_value' => 0,
            'total' => 100,
            'payment_method' => 'cash',
            'first_due_date' => '2026-09-01',
            'installments' => 1,
            'accepted_terms' => 'pending',
            'visibility' => Visibility::VISIBLE->value,
            'plan_id' => $plan->id,
            'coupon_id' => $coupon?->id,
            'registration_token' => 'qr-token-'.Str::lower($plan->public_slug),
        ]);
    }
}
