<?php

declare(strict_types=1);

namespace Tests\Feature\HiringLeads;

use App\Enums\ClientStatus;
use App\Enums\HiringLeadSource;
use App\Enums\Visibility;
use App\Mail\TemplateEmail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Coupon;
use App\Models\GatewayAccount;
use App\Models\HiringLead;
use App\Models\Plan;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
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

    public function test_registration_page_exposes_privacy_notice(): void
    {
        Setting::query()->create([
            'name' => 'privacy_notice',
            'label' => 'Aviso de Privacidade (LGPD)',
            'content' => 'Seus dados são tratados conforme a LGPD.',
            'object_type' => 'textarea',
            'group' => 'peoples',
        ]);

        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/Registration')
                ->where('privacyNotice', 'Seus dados são tratados conforme a LGPD.')
            );
    }

    public function test_registration_page_omits_privacy_notice_when_not_configured(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/Registration')
                ->where('privacyNotice', null)
            );
    }

    public function test_pre_registration_collects_the_document(): void
    {
        $this->post('/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'document' => '99887766554',
            'accepted' => true,
        ])->assertRedirect('/register');

        $this->assertDatabaseHas('hiring_leads', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'document' => '99887766554',
            'source' => HiringLeadSource::SITE->value,
        ]);
    }

    public function test_pre_registration_requires_the_document(): void
    {
        $this->post('/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
        ])->assertSessionHasErrors('document');

        $this->assertDatabaseCount('hiring_leads', 0);
    }

    public function test_pre_registration_does_not_require_terms_acceptance(): void
    {
        $this->post('/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'document' => '99887766554',
        ])->assertRedirect('/register');

        $this->assertDatabaseHas('hiring_leads', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'document' => '99887766554',
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
            'document' => '99887766554',
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
            'document' => '99887766554',
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

    public function test_contract_preview_returns_clauses_as_json(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $response = $this->getJson(route('public.contract-preview', [
            'contract' => $contract->registration_token,
        ]));

        $response->assertOk();

        $this->assertStringContainsString(
            'CONTRATO DE PRESTAÇÃO DE SERVIÇO',
            (string) $response->json('content'),
        );
    }

    public function test_contract_preview_applies_coupon_discount_keeping_terms_pending(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan, $this->createCoupon('PROMO10'));

        $this->get(route('public.contract-preview', [
            'contract' => $contract->registration_token,
        ]))->assertOk()->assertSee('R$ 90,00');

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'gross_value' => 100,
            'discount_value' => 10,
            'total' => 90,
            'accepted_terms' => 'pending',
        ]);

        $this->assertDatabaseCount('invoices', 0);
        $this->assertNull($contract->fresh()->client_id);
    }

    public function test_contract_preview_discount_is_idempotent(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan, $this->createCoupon('PROMO10'));

        $url = route('public.contract-preview', ['contract' => $contract->registration_token]);

        $this->get($url)->assertOk();
        $this->get($url)->assertOk();

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'discount_value' => 10,
            'total' => 90,
        ]);
    }

    public function test_contract_preview_keeps_values_after_terms_accepted(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan, $this->createCoupon('PROMO10'));
        $contract->update([
            'discount_value' => 25,
            'total' => 75,
            'accepted_terms' => 'accepted',
        ]);

        $this->get(route('public.contract-preview', [
            'contract' => $contract->registration_token,
        ]))->assertOk();

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'discount_value' => 25,
            'total' => 75,
            'accepted_terms' => 'accepted',
        ]);
    }

    public function test_contract_preview_uses_plan_cancellation_fee_percentage(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $plan->update(['cancellation_fee_percentage' => 30]);
        $contract = $this->createPendingContract($plan, $this->createCoupon('PROMO10'));

        $this->get(route('public.contract-preview', [
            'contract' => $contract->registration_token,
        ]))
            ->assertOk()
            ->assertSee('correspondente a 30%')
            ->assertSee('fixada em R$ 27,00');
    }

    public function test_contract_preview_falls_back_to_configured_cancellation_fee_percentage(): void
    {
        Setting::query()->create([
            'name' => 'cancellation_fee_percentage',
            'label' => 'Percentual da multa de cancelamento (%)',
            'content' => '15',
            'object_type' => 'number',
            'group' => 'billing',
        ]);

        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan, $this->createCoupon('PROMO10'));

        $this->get(route('public.contract-preview', [
            'contract' => $contract->registration_token,
        ]))
            ->assertOk()
            ->assertSee('correspondente a 15%')
            ->assertSee('fixada em R$ 13,50');
    }

    public function test_contract_preview_without_coupon_keeps_gross_total(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $this->get(route('public.contract-preview', [
            'contract' => $contract->registration_token,
        ]))->assertOk();

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'discount_value' => 0,
            'total' => 100,
            'accepted_terms' => 'pending',
        ]);
    }

    public function test_card_decline_keeps_contract_pending_and_notifies_company(): void
    {
        Mail::fake();
        Queue::fake();

        GatewayAccount::factory()->create([
            'name' => 'Asaas',
            'settings' => [
                'api_key' => 'test-api-key',
                'base_url' => 'https://sandbox.asaas.com/api/v3',
            ],
        ]);

        Setting::query()->create([
            'name' => 'billing_failure_notification_email',
            'label' => 'E-mail para avisos de falha de cobrança',
            'content' => 'financeiro@ct12rounds.test',
            'object_type' => 'text',
            'group' => 'billing',
        ]);

        Http::fake([
            'sandbox.asaas.com/api/v3/customers*' => Http::response(['id' => 'cus_123']),
            'sandbox.asaas.com/api/v3/creditCard/tokenize*' => Http::response(
                ['message' => 'Cartão Recusado'],
                422,
            ),
        ]);

        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan, $this->createCoupon('PROMO10'));
        $contract->update([
            'first_due_date' => now()->toDateString(),
            'payment_method' => 'credit_card',
        ]);

        $response = $this->post('/register', [
            'contract' => $contract->registration_token,
            ...$this->contractPayload(),
        ]);

        $response->assertSessionHasErrors('card_number');

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'accepted_terms' => 'pending',
        ]);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertNull($contract->fresh()->client_id);
        $this->assertDatabaseHas('clients', ['document' => '99887766554', 'status' => ClientStatus::PENDING->value]);

        Mail::assertQueued(TemplateEmail::class, function (TemplateEmail $email): bool {
            return $email->hasTo('financeiro@ct12rounds.test');
        });
    }

    public function test_billing_failure_notification_falls_back_to_mail_sender_address(): void
    {
        Mail::fake();

        GatewayAccount::factory()->create([
            'name' => 'Asaas',
            'settings' => [
                'api_key' => 'test-api-key',
                'base_url' => 'https://sandbox.asaas.com/api/v3',
            ],
        ]);

        Http::fake([
            'sandbox.asaas.com/api/v3/customers*' => Http::response(['id' => 'cus_123']),
            'sandbox.asaas.com/api/v3/creditCard/tokenize*' => Http::response(
                ['message' => 'Cartão Recusado'],
                422,
            ),
        ]);

        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $this->post('/register', [
            'contract' => $contract->registration_token,
            ...$this->contractPayload(),
        ])->assertSessionHasErrors('card_number');

        Mail::assertQueued(TemplateEmail::class, function (TemplateEmail $email): bool {
            return $email->hasTo((string) config('mail.from.address'));
        });
    }

    public function test_invoice_issuance_failure_notifies_company_and_keeps_contract_pending(): void
    {
        Mail::fake();
        $this->fakeGateway();

        Setting::query()->create([
            'name' => 'billing_failure_notification_email',
            'label' => 'E-mail para avisos de falha de cobrança',
            'content' => 'financeiro@ct12rounds.test',
            'object_type' => 'text',
            'group' => 'billing',
        ]);

        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan, $this->createCoupon('PROMO10'));

        // Contrato sem first due date impede a geração das faturas.
        $contract->update(['first_due_date' => null]);

        $this->post('/register', [
            'contract' => $contract->registration_token,
            ...$this->contractPayload(),
        ])->assertSessionHasErrors();

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'accepted_terms' => 'pending',
        ]);
        $this->assertDatabaseCount('invoices', 0);

        Mail::assertQueued(TemplateEmail::class, function (TemplateEmail $email): bool {
            return $email->hasTo('financeiro@ct12rounds.test');
        });
    }

    public function test_gateway_refusal_during_sync_reverts_contract_and_shows_error(): void
    {
        Mail::fake();

        $this->fakeGateway();

        Setting::query()->create([
            'name' => 'billing_failure_notification_email',
            'label' => 'E-mail para avisos de falha de cobrança',
            'content' => 'financeiro@ct12rounds.test',
            'object_type' => 'text',
            'group' => 'billing',
        ]);

        Http::fake([
            'sandbox.asaas.com/api/v3/customers*' => Http::response(['id' => 'cus_123']),
            'sandbox.asaas.com/api/v3/creditCard/tokenize*' => Http::response([
                'creditCardToken' => 'tok_123',
                'creditCardNumber' => '4111',
                'creditCardBrand' => 'VISA',
            ]),
            'sandbox.asaas.com/api/v3/payments' => Http::response(
                ['message' => 'Cartão Recusado'],
                400,
            ),
        ]);

        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan, $this->createCoupon('PROMO10'));
        $contract->update([
            'first_due_date' => now()->toDateString(),
            'payment_method' => 'credit_card',
        ]);

        $response = $this->post('/register', [
            'contract' => $contract->registration_token,
            ...$this->contractPayload(),
        ]);

        $response->assertSessionHasErrors('card_number');
        $response->assertSessionMissing('hiring_lead_success');

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'accepted_terms' => 'pending',
        ]);
        $this->assertDatabaseHas('clients', [
            'document' => '99887766554',
            'status' => ClientStatus::PENDING->value,
        ]);

        // A empresa recebe um unico aviso, enviado pela acao de reversao.
        Mail::assertQueued(TemplateEmail::class, 1);
    }

    public function test_successful_registration_charges_the_gateway_synchronously(): void
    {
        Mail::fake();
        Queue::fake();

        $this->fakeGateway();

        Http::fake([
            'sandbox.asaas.com/api/v3/customers*' => Http::response(['id' => 'cus_123']),
            'sandbox.asaas.com/api/v3/creditCard/tokenize*' => Http::response([
                'creditCardToken' => 'tok_123',
                'creditCardNumber' => '4111',
                'creditCardBrand' => 'VISA',
            ]),
            'sandbox.asaas.com/api/v3/payments' => Http::response([
                'id' => 'pay_sync_1',
                'billingType' => 'CREDIT_CARD',
                'status' => 'CONFIRMED',
                'value' => 90.0,
                'netValue' => 87.3,
                'paymentDate' => '2026-09-26T12:00:00Z',
            ]),
        ]);

        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan, $this->createCoupon('PROMO10'));
        $contract->update([
            'first_due_date' => now()->toDateString(),
            'payment_method' => 'credit_card',
        ]);

        $this->post('/register', [
            'contract' => $contract->registration_token,
            ...$this->contractPayload(),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'accepted_terms' => 'accepted',
            'total' => 90,
        ]);

        // A cobranca ocorreu na requisicao, com o token do cartao.
        Http::assertSent(function ($request): bool {
            $body = $request->data();

            return str_ends_with($request->url(), '/payments')
                && ($body['billingType'] ?? null) === 'CREDIT_CARD'
                && ($body['creditCardToken'] ?? null) === 'tok_123';
        });

        $this->assertDatabaseHas('gateway_payments', [
            'gateway_reference_key' => 'pay_sync_1',
        ]);
    }

    public function test_gateway_refusal_does_not_consume_coupon_use(): void
    {
        Mail::fake();

        $this->fakeGateway();

        Http::fake([
            'sandbox.asaas.com/api/v3/customers*' => Http::response(['id' => 'cus_123']),
            'sandbox.asaas.com/api/v3/creditCard/tokenize*' => Http::response([
                'creditCardToken' => 'tok_123',
                'creditCardNumber' => '4111',
                'creditCardBrand' => 'VISA',
            ]),
            'sandbox.asaas.com/api/v3/payments' => Http::response(
                ['message' => 'Cartão Recusado'],
                400,
            ),
        ]);

        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $coupon = $this->createCoupon('PROMO10');
        $contract = $this->createPendingContract($plan, $coupon);
        $contract->update([
            'first_due_date' => now()->toDateString(),
            'payment_method' => 'credit_card',
        ]);

        $this->post('/register', [
            'contract' => $contract->registration_token,
            ...$this->contractPayload(),
        ])->assertSessionHasErrors('card_number');

        $this->assertSame(0, $coupon->fresh()->used_count);
    }

    public function test_card_decline_does_not_consume_coupon_use(): void
    {
        Mail::fake();

        GatewayAccount::factory()->create([
            'name' => 'Asaas',
            'settings' => [
                'api_key' => 'test-api-key',
                'base_url' => 'https://sandbox.asaas.com/api/v3',
            ],
        ]);

        Http::fake([
            'sandbox.asaas.com/api/v3/customers*' => Http::response(['id' => 'cus_123']),
            'sandbox.asaas.com/api/v3/creditCard/tokenize*' => Http::response(
                ['message' => 'Cartão Recusado'],
                422,
            ),
        ]);

        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $coupon = $this->createCoupon('PROMO10');
        $contract = $this->createPendingContract($plan, $coupon);

        $this->post('/register', [
            'contract' => $contract->registration_token,
            ...$this->contractPayload(),
        ])->assertSessionHasErrors('card_number');

        $this->assertSame(0, $coupon->fresh()->used_count);
    }

    public function test_retry_after_gateway_refusal_consumes_coupon_only_once(): void
    {
        Mail::fake();

        $this->fakeGateway();

        Http::fake([
            'sandbox.asaas.com/api/v3/customers*' => Http::response(['id' => 'cus_123']),
            'sandbox.asaas.com/api/v3/creditCard/tokenize*' => Http::response([
                'creditCardToken' => 'tok_123',
                'creditCardNumber' => '4111',
                'creditCardBrand' => 'VISA',
            ]),
            'sandbox.asaas.com/api/v3/payments' => Http::sequence()
                ->push(['message' => 'Cartão Recusado'], 400)
                ->push([
                    'id' => 'pay_retry_1',
                    'billingType' => 'CREDIT_CARD',
                    'status' => 'CONFIRMED',
                    'value' => 90.0,
                ]),
        ]);

        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $coupon = $this->createCoupon('PROMO10');
        $contract = $this->createPendingContract($plan, $coupon);
        $contract->update([
            'first_due_date' => now()->toDateString(),
            'payment_method' => 'credit_card',
        ]);

        $payload = [
            'contract' => $contract->registration_token,
            ...$this->contractPayload(),
        ];

        $this->post('/register', $payload)->assertSessionHasErrors('card_number');
        $this->assertSame(0, $coupon->fresh()->used_count);

        $this->post('/register/retry-payment', $payload)->assertSessionHasNoErrors();

        $this->assertSame(1, $coupon->fresh()->used_count);
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
