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

    public function test_cadastro_de_promocao_cria_lead_source_site(): void
    {
        $this->post('/cadastro', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'document' => '12345678901',
            'accepted' => true,
        ])->assertRedirect('/cadastro');

        $this->assertDatabaseHas('hiring_leads', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'document' => '12345678901',
            'source' => HiringLeadSource::SITE->value,
        ]);
    }

    public function test_cadastro_requer_aceite_dos_termos(): void
    {
        $this->post('/cadastro', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'document' => '12345678901',
        ])->assertSessionHasErrors('accepted');

        $this->assertDatabaseCount('hiring_leads', 0);
    }

    public function test_cadastro_contrato_requer_endereco_completo(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Mensal',
            'public_slug' => 'mensal',
            'visibility' => Visibility::VISIBLE->value,
        ]);

        $this->post('/cadastro', [
            'plan' => $plan->public_slug,
            'name' => 'Joao Souza',
            'email' => 'joao@example.com',
            'phone' => '11988888888',
            'document' => '99887766554',
            'gender' => 'M',
            'birth_date' => '1990-01-01',
            'accepted' => true,
        ])->assertSessionHasErrors(['address', 'address_number', 'address_district', 'address_state', 'address_city', 'address_postal_code']);
    }

    public function test_cadastro_contrato_cria_lead_com_plano(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Mensal',
            'public_slug' => 'mensal',
            'visibility' => Visibility::VISIBLE->value,
        ]);

        $this->post('/cadastro', [
            'plan' => $plan->public_slug,
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
            'accepted' => true,
        ])->assertRedirect('/cadastro');

        $this->assertDatabaseHas('hiring_leads', [
            'document' => '99887766554',
            'source' => HiringLeadSource::CONTRACT->value,
            'plan_id' => $plan->id,
            'address_city' => 'Sao Paulo',
        ]);
    }

    public function test_cadastro_aplica_cupom_valido(): void
    {
        $coupon = Coupon::query()->create([
            'code' => 'BEMVINDO10',
            'percent' => 10,
            'visibility' => Visibility::VISIBLE->value,
        ]);

        $this->post('/cadastro', [
            'name' => 'Ana Lima',
            'email' => 'ana@example.com',
            'phone' => '11977777777',
            'document' => '45678912301',
            'coupon' => $coupon->code,
            'accepted' => true,
        ])->assertRedirect('/cadastro');

        $this->assertDatabaseHas('hiring_leads', [
            'document' => '45678912301',
            'coupon_id' => $coupon->id,
        ]);
    }

    public function test_cadastro_rejeita_cupom_expirado(): void
    {
        Coupon::query()->create([
            'code' => 'EXPIRADO',
            'percent' => 10,
            'expiration_date' => now()->subDay()->toDateString(),
            'visibility' => Visibility::VISIBLE->value,
        ]);

        $this->post('/cadastro', [
            'name' => 'Ana Lima',
            'email' => 'ana@example.com',
            'phone' => '11977777777',
            'document' => '45678912301',
            'coupon' => 'EXPIRADO',
            'accepted' => true,
        ])->assertSessionHasErrors('coupon');
    }

    public function test_cadastro_page_exposes_contract_registration_by_token(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $contract = $this->createPendingContract($plan);

        $this->get('/cadastro?contract='.$contract->registration_token)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/Cadastro')
                ->where('contract.id', $contract->id)
                ->where('contract.token', $contract->registration_token)
                ->where('plan.id', $plan->id)
            );
    }

    public function test_cadastro_por_token_cria_lead_vinculado_ao_contrato(): void
    {
        $plan = $this->createPlanWithContract('Mensal', 'mensal');
        $coupon = Coupon::query()->create([
            'code' => 'QR10',
            'percent' => 10,
            'visibility' => Visibility::VISIBLE->value,
        ]);
        $contract = $this->createPendingContract($plan, $coupon);

        $this->post('/cadastro', [
            'contract' => $contract->registration_token,
            'name' => 'Maria QR',
            'email' => 'mariaqr@example.com',
            'phone' => '11988889999',
            'document' => '11122233344',
            'gender' => 'F',
            'birth_date' => '1990-01-01',
            'address' => 'Rua das Flores',
            'address_number' => '100',
            'address_district' => 'Centro',
            'address_state' => 'SP',
            'address_city' => 'Sao Paulo',
            'address_postal_code' => '01001000',
            'accepted' => true,
        ])->assertRedirect('/cadastro');

        $this->assertDatabaseHas('hiring_leads', [
            'document' => '11122233344',
            'source' => HiringLeadSource::CONTRACT->value,
            'plan_id' => $plan->id,
            'coupon_id' => $coupon->id,
            'contract_id' => $contract->id,
        ]);
    }

    public function test_cadastro_rejeita_token_de_contrato_invalido(): void
    {
        $this->post('/cadastro', [
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
        ])->assertSessionHasErrors('contract');

        $this->assertDatabaseCount('hiring_leads', 0);
    }

    private function createPlanWithContract(string $name, string $slug): Plan
    {
        return Plan::query()->create([
            'name' => $name,
            'public_slug' => $slug,
            'visibility' => Visibility::VISIBLE->value,
        ]);
    }

    private function createPendingContract(Plan $plan, ?Coupon $coupon = null): Contract
    {
        return Contract::query()->create([
            'plan_name' => $plan->name,
            'modality_quantity' => '1',
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
