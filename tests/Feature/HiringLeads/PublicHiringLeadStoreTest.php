<?php

declare(strict_types=1);

namespace Tests\Feature\HiringLeads;

use App\Enums\ClientStatus;
use App\Enums\HiringLeadSource;
use App\Enums\Visibility;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Coupon;
use App\Models\GatewayAccount;
use App\Models\HiringLead;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_pre_registration_does_not_collect_document(): void
    {
        $this->post('/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'accepted' => true,
        ])->assertRedirect('/register');

        $this->assertDatabaseHas('hiring_leads', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'document' => null,
            'source' => HiringLeadSource::SITE->value,
        ]);
    }

    public function test_pre_registration_does_not_require_terms_acceptance(): void
    {
        $this->post('/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
        ])->assertRedirect('/register');

        $this->assertDatabaseHas('hiring_leads', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'document' => null,
            'source' => HiringLeadSource::SITE->value,
        ]);
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

    public function test_registration_page_resolves_coupon_code(): void
    {
        $this->createCoupon('PROMO10');

        $this->get('/register?coupon=PROMO10')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/Registration')
                ->where('coupon', 'PROMO10')
                ->where('couponWarning', null)
            );
    }

    public function test_pre_registration_with_valid_coupon_reserves_usage(): void
    {
        $coupon = $this->createCoupon('PROMO10');

        $this->post('/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'coupon' => 'PROMO10',
        ])->assertRedirect('/register');

        $this->assertDatabaseHas('hiring_leads', [
            'email' => 'maria@example.com',
            'coupon_id' => $coupon->id,
            'source' => HiringLeadSource::SITE->value,
        ]);

        $this->assertSame(1, $coupon->fresh()->used_count);
    }

    public function test_pre_registration_with_unavailable_coupon_does_not_reserve(): void
    {
        $this->post('/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'coupon' => 'CUPOM-INEXISTENTE',
        ])->assertRedirect('/register');

        $this->assertDatabaseHas('hiring_leads', [
            'email' => 'maria@example.com',
            'coupon_id' => null,
            'source' => HiringLeadSource::SITE->value,
        ]);
    }

    public function test_registration_page_prefills_data_from_linked_lead(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        HiringLead::query()->create([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'source' => HiringLeadSource::SITE->value,
            'status' => 'new',
            'visibility' => Visibility::VISIBLE->value,
            'contract_id' => $contract->id,
        ]);

        $this->get('/register?contract='.$contract->registration_token)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/Registration')
                ->where('contract.id', $contract->id)
                ->where('initial.name', 'Maria Silva')
                ->where('initial.email', 'maria@example.com')
                ->where('initial.phone', '11999999999')
            );
    }

    public function test_registration_page_exposes_contract_preview(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $this->get('/register?contract='.$contract->registration_token)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/Registration')
                ->where('contract.preview_url', route('public.contract-preview', [
                    'contract' => $contract->registration_token,
                ]))
            );
    }

    public function test_contract_preview_renders_contract_clauses(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $this->get(route('public.contract-preview', [
            'contract' => $contract->registration_token,
        ]))
            ->assertOk()
            ->assertSee('CONTRATO DE PRESTAÇÃO DE SERVIÇO')
            ->assertSee($plan->name);
    }

    public function test_contract_advance_creates_pending_client(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $response = $this->post('/register/prepare-client', [
            'contract' => $contract->registration_token,
            ...$this->clientPayload(),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('registration_pending_client', [
            'contract' => $contract->registration_token,
            'client_id' => Client::query()->where('document', '99887766554')->value('id'),
        ]);

        $this->assertDatabaseHas('clients', [
            'name' => 'Joao Souza',
            'document' => '99887766554',
            'status' => ClientStatus::PENDING->value,
        ]);

        $this->assertNull($contract->fresh()->client_id);
    }

    public function test_contract_advance_reuses_existing_client(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $client = Client::factory()->create([
            'document' => '99887766554',
            'status' => ClientStatus::ACTIVE->value,
        ]);

        $response = $this->post('/register/prepare-client', [
            'contract' => $contract->registration_token,
            ...$this->clientPayload(),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('registration_pending_client.client_id', $client->id);

        $this->assertDatabaseCount('clients', 1);
        $this->assertSame(ClientStatus::ACTIVE, $client->fresh()->status);
    }

    public function test_contract_advance_updates_pending_client_data(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $client = Client::factory()->create([
            'document' => '99887766554',
            'status' => ClientStatus::PENDING->value,
            'address_city' => 'Cidade Antiga',
        ]);

        $this->post('/register/prepare-client', [
            'contract' => $contract->registration_token,
            ...$this->clientPayload(),
        ])
            ->assertSessionHasNoErrors();

        $client->refresh();

        $this->assertSame('Sao Paulo', $client->address_city);
        $this->assertSame(ClientStatus::PENDING, $client->status);
    }

    public function test_contract_advance_rejects_invalid_contract_token(): void
    {
        $this->post('/register/prepare-client', [
            ...$this->clientPayload(),
            'contract' => 'token-inexistente',
        ])->assertSessionHasErrors('contract');

        $this->assertDatabaseCount('clients', 0);
    }

    public function test_contract_advance_rejects_client_with_active_contract(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $client = Client::factory()->create([
            'document' => '99887766554',
            'status' => ClientStatus::ACTIVE->value,
        ]);

        $this->createClientContract($client, 'open');

        $response = $this->post('/register/prepare-client', [
            'contract' => $contract->registration_token,
            ...$this->clientPayload(),
        ]);

        $response->assertSessionHasErrors('document');
        $response->assertSessionMissing('registration_pending_client');

        $this->assertSame(ClientStatus::ACTIVE, $client->fresh()->status);
    }

    public function test_contract_advance_allows_client_with_canceled_contract(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $client = Client::factory()->create([
            'document' => '99887766554',
            'status' => ClientStatus::ACTIVE->value,
        ]);

        $this->createClientContract($client, 'canceled');

        $this->post('/register/prepare-client', [
            'contract' => $contract->registration_token,
            ...$this->clientPayload(),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('clients', 1);
    }

    public function test_contract_registration_rejects_client_with_active_contract(): void
    {
        $this->fakeGateway();

        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $client = Client::factory()->create([
            'document' => '99887766554',
            'status' => ClientStatus::ACTIVE->value,
        ]);

        $this->createClientContract($client, 'open');

        $this->post('/register', [
            'contract' => $contract->registration_token,
            ...$this->contractPayload(),
        ])->assertSessionHasErrors('document');

        $this->assertDatabaseCount('hiring_leads', 0);
        $this->assertNull($contract->fresh()->client_id);
    }

    public function test_contract_advance_requires_client_data(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $this->post('/register/prepare-client', [
            'contract' => $contract->registration_token,
            ...$this->clientPayload(),
            'document' => '',
            'address_city' => '',
        ])->assertSessionHasErrors(['document', 'address_city']);

        $this->assertDatabaseCount('clients', 0);
    }

    public function test_contract_advance_ignores_client_from_another_contract(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $client = Client::factory()->create([
            'document' => '11122233344',
            'status' => ClientStatus::PENDING->value,
        ]);

        $this->withSession([
            'registration_pending_client' => [
                'contract' => 'outro-token',
                'client_id' => $client->id,
            ],
        ]);

        $this->get(route('public.contract-preview', [
            'contract' => $contract->registration_token,
        ]))->assertOk()->assertDontSee($client->name);
    }

    public function test_contract_preview_uses_pending_client(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $this->post('/register/prepare-client', [
            'contract' => $contract->registration_token,
            ...$this->clientPayload(),
        ])
            ->assertSessionHasNoErrors();

        $this->get(route('public.contract-preview', [
            'contract' => $contract->registration_token,
        ]))
            ->assertOk()
            ->assertSee('Joao Souza')
            ->assertSee('99887766554')
            ->assertSee('Rua das Flores, 100, Centro, Sao Paulo, SP, 01001000');
    }

    public function test_contract_registration_activates_pending_client(): void
    {
        $this->fakeGateway();

        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $this->post('/register/prepare-client', [
            'contract' => $contract->registration_token,
            ...$this->clientPayload(),
        ])
            ->assertSessionHasNoErrors();

        $response = $this->post('/register', [
            'contract' => $contract->registration_token,
            ...$this->contractPayload(),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionMissing('registration_pending_client');

        $client = Client::query()->where('document', '99887766554')->first();

        $this->assertNotNull($client);
        $this->assertSame(ClientStatus::ACTIVE, $client->status);
        $this->assertSame($client->id, $contract->fresh()->client_id);
        $this->assertDatabaseCount('clients', 1);
    }

    public function test_contract_registration_without_advance_activates_created_client(): void
    {
        $this->fakeGateway();

        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $this->post('/register', [
            'contract' => $contract->registration_token,
            ...$this->contractPayload(),
        ])->assertSessionHasNoErrors();

        $client = Client::query()->where('document', '99887766554')->first();

        $this->assertNotNull($client);
        $this->assertSame(ClientStatus::ACTIVE, $client->status);
    }

    public function test_contract_registration_does_not_require_image_rights_acceptance(): void
    {
        $this->fakeGateway();

        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $this->post('/register', [
            'contract' => $contract->registration_token,
            ...$this->contractPayload(),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('hiring_leads', 1);
    }

    public function test_contract_registration_does_not_record_image_rights_acceptance(): void
    {
        $this->fakeGateway();

        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $this->post('/register', [
            'contract' => $contract->registration_token,
            ...$this->contractPayload(),
        ])->assertSessionHasNoErrors();

        $lead = HiringLead::query()->where('contract_id', $contract->id)->first();

        $this->assertNotNull($lead);
        $this->assertNull($lead->image_rights_accepted_at);
    }

    public function test_contract_registration_auto_applies_the_contract(): void
    {
        $this->fakeGateway();

        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $this->post('/register', [
            'contract' => $contract->registration_token,
            ...$this->contractPayload(),
        ])->assertSessionHasNoErrors();

        $contract->refresh();

        $this->assertNotNull($contract->client_id);
        $this->assertSame('accepted', $contract->accepted_terms);
        $this->assertDatabaseCount('clients', 1);
        $this->assertDatabaseCount('invoices', 1);

        $lead = HiringLead::query()->where('contract_id', $contract->id)->first();

        $this->assertNotNull($lead);
        $this->assertSame($contract->client_id, $lead->client_id);
    }

    public function test_contract_registration_with_reserved_coupon_is_not_counted_again(): void
    {
        $this->fakeGateway();

        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $coupon = $this->createCoupon('PROMO10');
        $coupon->update(['used_count' => 1]);

        $contract = $this->createPendingContract($plan, $coupon);

        HiringLead::query()->create([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'source' => HiringLeadSource::SITE->value,
            'status' => 'new',
            'visibility' => Visibility::VISIBLE->value,
            'coupon_id' => $coupon->id,
            'contract_id' => $contract->id,
        ]);

        $this->post('/register', [
            'contract' => $contract->registration_token,
            ...$this->contractPayload(),
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $coupon->fresh()->used_count);

        $this->assertDatabaseHas('hiring_leads', [
            'contract_id' => $contract->id,
            'coupon_id' => $coupon->id,
            'source' => HiringLeadSource::CONTRACT->value,
        ]);
    }

    public function test_contract_registration_without_reservation_counts_coupon(): void
    {
        $this->fakeGateway();

        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $coupon = $this->createCoupon('PROMO10');
        $contract = $this->createPendingContract($plan, $coupon);

        $this->post('/register', [
            'contract' => $contract->registration_token,
            ...$this->contractPayload(),
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $coupon->fresh()->used_count);
    }

    private function createCoupon(string $code): Coupon
    {
        return Coupon::query()->create([
            'code' => $code,
            'percent' => 10,
            'discount_limit' => 100,
            'duration' => 1,
            'expiration_date' => '2026-12-31',
            'visibility' => Visibility::VISIBLE->value,
        ]);
    }

    private function fakeGateway(): void
    {
        GatewayAccount::factory()->create([
            'name' => 'Asaas',
            'settings' => [
                'api_key' => 'test-api-key',
                'base_url' => 'https://sandbox.asaas.com/api/v3',
            ],
        ]);

        Http::fake([
            'sandbox.asaas.com/api/v3/customers*' => Http::response(['id' => 'cus_123']),
            'sandbox.asaas.com/api/v3/creditCard/tokenize*' => Http::response([
                'creditCardToken' => 'tok_123',
                'creditCardNumber' => '4111',
                'creditCardBrand' => 'VISA',
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function contractPayload(): array
    {
        return [
            ...$this->clientPayload(),
            'accepted' => true,
            'is_contract_flow' => true,
            ...$this->validCardData,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function clientPayload(): array
    {
        return [
            'name' => 'Joao Souza',
            'email' => 'joao@example.com',
            'phone' => '11988888888',
            'document' => '99887766554',
            'gender' => 'M',
            'birth_date' => '1990-01-01',
            'address' => 'Rua das Flores',
            'address_number' => '100',
            'address_district' => 'Centro',
            'address_state' => 'SP',
            'address_city' => 'Sao Paulo',
            'address_postal_code' => '01001000',
        ];
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

    private function createClientContract(Client $client, string $status): Contract
    {
        return Contract::query()->create([
            'plan_name' => 'Mensal',
            'gross_value' => 100,
            'discount_value' => 0,
            'total' => 100,
            'payment_method' => 'cash',
            'first_due_date' => '2026-01-01',
            'installments' => 1,
            'accepted_terms' => 'accepted',
            'status' => $status,
            'visibility' => Visibility::VISIBLE->value,
            'client_id' => $client->id,
            'registration_token' => 'client-token-'.$client->id.'-'.$status,
        ]);
    }
}
