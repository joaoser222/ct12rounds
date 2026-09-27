<?php

namespace Tests\Feature\Actions;

use App\Actions\FinancialAccounts\CreateFinancialAccountAction;
use App\DTOs\FinancialAccounts\CreateFinancialAccountDTO;
use App\Enums\FinancialAccountType;
use App\Models\FinancialAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateFinancialAccountActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_cash_account_with_valid_data(): void
    {
        $action = app(CreateFinancialAccountAction::class);
        $dto = CreateFinancialAccountDTO::from([
            'name' => 'Caixa Principal',
            'account_type' => FinancialAccountType::CASH->value,
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('financial_accounts', [
            'name' => 'Caixa Principal',
            'account_type' => FinancialAccountType::CASH->value,
        ]);
    }

    public function test_creates_bank_account_with_valid_data(): void
    {
        $action = app(CreateFinancialAccountAction::class);
        $dto = CreateFinancialAccountDTO::from([
            'name' => 'Conta Bancária',
            'account_type' => FinancialAccountType::BANK->value,
            'holder_name' => 'Maria Silva',
            'holder_document' => '12345678909',
            'holder_birth_date' => '1990-01-01',
            'bank_account_number' => '12345',
            'bank_agency' => '0001',
            'bank_account_type' => 'checking',
            'bank_code' => '001',
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('financial_accounts', [
            'name' => 'Conta Bancária',
            'account_type' => FinancialAccountType::BANK->value,
        ]);
    }

    public function test_returns_success_message(): void
    {
        $action = app(CreateFinancialAccountAction::class);
        $dto = CreateFinancialAccountDTO::from([
            'name' => 'Caixa Teste',
            'account_type' => FinancialAccountType::CASH->value,
        ]);

        $result = $action->execute($dto);

        $this->assertSame('Conta financeira criada com sucesso.', $result->message);
    }

    public function test_cash_account_nullifies_bank_fields(): void
    {
        $action = app(CreateFinancialAccountAction::class);
        $dto = CreateFinancialAccountDTO::from([
            'name' => 'Caixa',
            'account_type' => FinancialAccountType::CASH->value,
            'holder_name' => 'Ignorado',
            'holder_document' => '999',
            'holder_birth_date' => '1990-01-01',
            'bank_account_number' => '99999',
            'bank_agency' => '9999',
            'bank_account_type' => 'savings',
            'bank_code' => '999',
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $account = FinancialAccount::query()->first();
        $this->assertNull($account->holder_name);
        $this->assertNull($account->bank_code);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(CreateFinancialAccountAction::class);
        $action->execute('not-a-dto');
    }
}
