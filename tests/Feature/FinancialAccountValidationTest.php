<?php

namespace Tests\Feature;

use App\Enums\FinancialAccountType;
use App\Models\FinancialAccount;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialAccountValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_account_only_updates_name_and_account_type(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'financial_accounts.update');

        $account = FinancialAccount::query()->create([
            'name' => 'Conta antiga',
            'account_type' => FinancialAccountType::BANK,
            'balance' => 1250,
            'holder_name' => 'Titular antigo',
            'holder_document' => '12345678909',
            'holder_birth_date' => '1990-01-01',
            'bank_account_number' => '12345',
            'bank_agency' => '0001',
            'bank_account_type' => 'checking',
            'bank_code' => '001',
        ]);

        $response = $this->actingAs($user)->putJson(route('financial-accounts.update', $account), [
            'name' => 'Caixa principal',
            'account_type' => FinancialAccountType::CASH->value,
            'balance' => 9999,
            'holder_name' => 'Titular ignorado',
            'holder_document' => '99999999999',
            'holder_birth_date' => '1980-01-01',
            'bank_account_number' => '99999',
            'bank_agency' => '9999',
            'bank_account_type' => 'savings',
            'bank_code' => '999',
        ]);

        $response->assertOk();

        $account->refresh();

        $this->assertSame('Caixa principal', $account->name);
        $this->assertSame(FinancialAccountType::CASH, $account->account_type);
        $this->assertSame('1250.0000', $account->getRawOriginal('balance'));
        $this->assertNull($account->holder_name);
        $this->assertNull($account->holder_document);
        $this->assertNull($account->holder_birth_date);
        $this->assertNull($account->bank_account_number);
        $this->assertNull($account->bank_agency);
        $this->assertNull($account->bank_account_type);
        $this->assertNull($account->bank_code);
    }

    public function test_bank_account_requires_bank_fields(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'financial_accounts.create');

        $response = $this->actingAs($user)->postJson(route('financial-accounts.store'), [
            'name' => 'Conta bancária',
            'account_type' => FinancialAccountType::BANK->value,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'holder_name',
            'holder_document',
            'holder_birth_date',
            'bank_account_number',
            'bank_agency',
            'bank_account_type',
            'bank_code',
        ]);
    }

    public function test_bank_account_can_be_created_with_required_bank_fields_without_balance(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'financial_accounts.create');

        $response = $this->actingAs($user)->postJson(route('financial-accounts.store'), [
            'name' => 'Conta bancária',
            'account_type' => FinancialAccountType::BANK->value,
            'balance' => 9999,
            'holder_name' => 'Maria Silva',
            'holder_document' => '12345678909',
            'holder_birth_date' => '1990-01-01',
            'bank_account_number' => '12345',
            'bank_agency' => '0001',
            'bank_account_type' => 'checking',
            'bank_code' => '001',
        ]);

        $response->assertCreated();

        $account = FinancialAccount::query()->firstOrFail();

        $this->assertSame('Conta bancária', $account->name);
        $this->assertSame(FinancialAccountType::BANK, $account->account_type);
        $this->assertSame('0.0000', $account->getRawOriginal('balance'));
    }

    protected function grantPermission(User $user, string $permission): void
    {
        $permission = Permission::query()->create([
            'name' => $permission,
            'description' => $permission,
        ]);

        $user->permissions()->attach($permission);
    }
}
