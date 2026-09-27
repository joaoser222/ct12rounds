<?php

namespace Tests\Feature;

use App\Enums\BillableStatus;
use App\Enums\GenderType;
use App\Enums\InvoiceStatus;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\DirectLesson;
use App\Models\Permission;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Console\QueuedCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class DirectLessonBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_lesson_can_generate_receivable_invoice_and_queue_gateway_sync(): void
    {
        Bus::fake();
        Date::setTestNow('2026-07-21');

        $user = User::factory()->create();
        $this->grantPermission($user, 'direct_lessons.create');
        $client = Client::factory()->create();
        $trainer = $this->createTrainer();

        $response = $this->actingAs($user)->post(route('direct-lessons.store'), [
            'client_id' => $client->id,
            'trainer_id' => $trainer->id,
            'lesson_date' => '2026-07-20',
            'price' => 120.50,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::PIX->value,
            'generate_invoices' => true,
        ]);

        $response->assertRedirect(route('direct-lessons.index'));

        $directLesson = DirectLesson::query()->firstOrFail();

        $this->assertDatabaseHas('invoices', [
            'operation_type' => OperationType::RECEIVABLE->value,
            'due_date' => '2026-07-21',
            'payment_method' => PaymentMethod::PIX->value,
            'gross_value' => 120.50,
            'discount_value' => 0,
            'installment_number' => 1,
            'status' => InvoiceStatus::PENDING->value,
            'holder_id' => $client->id,
            'holder_type' => 'client',
            'billable_id' => $directLesson->id,
            'billable_type' => 'direct_lesson',
        ]);

        Bus::assertDispatched(
            QueuedCommand::class,
            fn (QueuedCommand $command): bool => $command->displayName() === 'gateway:sync-invoices',
        );
    }

    public function test_direct_lesson_date_cannot_be_after_today(): void
    {
        Date::setTestNow('2026-07-21');

        $user = User::factory()->create();
        $this->grantPermission($user, 'direct_lessons.create');
        $client = Client::factory()->create();
        $trainer = $this->createTrainer();

        $response = $this->actingAs($user)->postJson(route('direct-lessons.store'), [
            'client_id' => $client->id,
            'trainer_id' => $trainer->id,
            'lesson_date' => '2026-07-22',
            'price' => 120.50,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::CASH->value,
            'generate_invoices' => false,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['lesson_date']);
    }

    protected function grantPermission(User $user, string $permission): void
    {
        $permission = Permission::query()->create([
            'name' => $permission,
            'description' => $permission,
        ]);

        $user->permissions()->attach($permission);
    }

    private function createTrainer(): Trainer
    {
        return Trainer::query()->create([
            'name' => 'Treinador Teste',
            'email' => 'treinador@example.com',
            'document' => '12345678909',
            'birth_date' => '1990-01-01',
            'phone' => '11999999999',
            'gender' => GenderType::MALE->value,
            'visibility' => 'visible',
        ]);
    }
}
