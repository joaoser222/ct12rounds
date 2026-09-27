<?php

namespace Tests\Feature\Actions;

use App\Actions\FinancialAccounts\UpdateFinancialAccountAction;
use App\DTOs\FinancialAccounts\UpdateFinancialAccountDTO;
use App\Enums\FinancialAccountType;
use App\Models\FinancialAccount;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateFinancialAccountActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_financial_account_with_valid_data(): void
    {
        $account = FinancialAccount::query()->create([
            'name' => 'Conta Antiga',
            'account_type' => FinancialAccountType::CASH,
        ]);

        $action = app(UpdateFinancialAccountAction::class);
        $dto = UpdateFinancialAccountDTO::from([
            'id' => $account->id,
            'name' => 'Conta Nova',
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('financial_accounts', [
            'id' => $account->id,
            'name' => 'Conta Nova',
        ]);
    }

    public function test_returns_success_message(): void
    {
        $account = FinancialAccount::query()->create([
            'name' => 'Atualizar',
            'account_type' => FinancialAccountType::CASH,
        ]);

        $action = app(UpdateFinancialAccountAction::class);
        $dto = UpdateFinancialAccountDTO::from([
            'id' => $account->id,
            'name' => 'Atualizado',
        ]);

        $result = $action->execute($dto);

        $this->assertSame('Conta financeira atualizada com sucesso.', $result->message);
    }

    public function test_switching_to_cash_nullifies_bank_fields(): void
    {
        $account = FinancialAccount::query()->create([
            'name' => 'Bancária',
            'account_type' => FinancialAccountType::BANK,
            'holder_name' => 'Titular Antigo',
            'holder_document' => '12345678909',
            'holder_birth_date' => '1990-01-01',
            'bank_account_number' => '12345',
            'bank_agency' => '0001',
            'bank_account_type' => 'checking',
            'bank_code' => '001',
        ]);

        $action = app(UpdateFinancialAccountAction::class);
        $dto = UpdateFinancialAccountDTO::from([
            'id' => $account->id,
            'account_type' => FinancialAccountType::CASH->value,
            'name' => 'Agora Caixa',
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $account->refresh();
        $this->assertNull($account->holder_name);
        $this->assertNull($account->bank_code);
    }

    public function test_throws_when_account_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $action = app(UpdateFinancialAccountAction::class);
        $dto = UpdateFinancialAccountDTO::from([
            'id' => 999999,
            'name' => 'Inexistente',
        ]);
        $action->execute($dto);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(UpdateFinancialAccountAction::class);
        $action->execute('not-a-dto');
    }
}
