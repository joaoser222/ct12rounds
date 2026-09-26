<?php

namespace Tests\Feature\Actions;

use App\Actions\Contracts\RevertContractAcceptanceAction;
use App\Enums\BillableStatus;
use App\Enums\ClientStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Mail\TemplateEmail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Sale;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RevertContractAcceptanceActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_gateway_refusal_reverts_contract_and_client_and_notifies_company(): void
    {
        Mail::fake();

        $this->createNotificationSetting();

        [$contract, $client, $invoice] = $this->makeAcceptedContract();

        $result = app(RevertContractAcceptanceAction::class)->execute($invoice);

        $this->assertTrue($result->success);

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'accepted_terms' => 'pending',
            'client_id' => null,
        ]);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'status' => ClientStatus::PENDING->value,
        ]);

        // A fatura permanece registrada para o proximo ciclo de aplicacao.
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);

        Mail::assertQueued(TemplateEmail::class, function (TemplateEmail $email): bool {
            return $email->hasTo('financeiro@ct12rounds.test');
        });
    }

    public function test_contract_with_paid_invoice_is_not_reverted(): void
    {
        Mail::fake();

        $this->createNotificationSetting();

        [$contract, $client, $invoice] = $this->makeAcceptedContract();
        $invoice->update(['status' => InvoiceStatus::PAID->value]);

        app(RevertContractAcceptanceAction::class)->execute($invoice);

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'accepted_terms' => 'accepted',
        ]);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'status' => ClientStatus::ACTIVE->value,
        ]);

        Mail::assertNothingQueued();
    }

    public function test_contract_already_pending_is_left_untouched(): void
    {
        Mail::fake();

        $this->createNotificationSetting();

        [$contract, , $invoice] = $this->makeAcceptedContract();
        $contract->update(['accepted_terms' => 'pending']);

        app(RevertContractAcceptanceAction::class)->execute($invoice);

        $this->assertDatabaseHas('clients', [
            'status' => ClientStatus::ACTIVE->value,
        ]);

        Mail::assertNothingQueued();
    }

    public function test_invoice_outside_a_contract_is_ignored(): void
    {
        Mail::fake();

        $this->createNotificationSetting();

        $client = Client::factory()->create(['status' => ClientStatus::ACTIVE->value]);
        $sale = Sale::query()->create([
            'client_id' => $client->id,
            'total' => 100,
            'status' => BillableStatus::OPEN,
            'visibility' => 'visible',
        ]);

        $invoice = $this->makeInvoice($sale, $client);

        $result = app(RevertContractAcceptanceAction::class)->execute($invoice);

        $this->assertTrue($result->success);
        Mail::assertNothingQueued();
    }

    private function createNotificationSetting(): void
    {
        Setting::query()->create([
            'name' => 'billing_failure_notification_email',
            'label' => 'E-mail para avisos de falha de cobrança',
            'content' => 'financeiro@ct12rounds.test',
            'object_type' => 'text',
            'group' => 'billing',
        ]);
    }

    /**
     * @return array{0: Contract, 1: Client, 2: Invoice}
     */
    private function makeAcceptedContract(): array
    {
        $client = Client::factory()->create(['status' => ClientStatus::ACTIVE->value]);

        $contract = Contract::query()->create([
            'plan_name' => 'Mensal',
            'gross_value' => 100,
            'discount_value' => 0,
            'total' => 100,
            'payment_method' => PaymentMethod::CREDIT_CARD->value,
            'first_due_date' => now()->toDateString(),
            'installments' => 1,
            'accepted_terms' => 'accepted',
            'client_id' => $client->id,
            'visibility' => 'visible',
        ]);

        return [$contract, $client, $this->makeInvoice($contract, $client)];
    }

    private function makeInvoice(object $billable, Client $client): Invoice
    {
        return Invoice::query()->create([
            'operation_type' => OperationType::RECEIVABLE->value,
            'payment_method' => PaymentMethod::CREDIT_CARD->value,
            'due_date' => now()->toDateString(),
            'gross_value' => 100,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 0,
            'installment_number' => 1,
            'status' => InvoiceStatus::PENDING->value,
            'visibility' => 'visible',
            'holder_id' => $client->id,
            'holder_type' => $client->getMorphClass(),
            'billable_id' => $billable->getKey(),
            'billable_type' => $billable->getMorphClass(),
        ]);
    }
}
