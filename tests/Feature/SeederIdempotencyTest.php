<?php

namespace Tests\Feature;

use App\Enums\FinancialAccountType;
use App\Enums\GenderType;
use App\Enums\OperationType;
use App\Models\CostCenter;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\Supplier;
use App\Models\Trainer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The seeders used to upsert with a hardcoded 'id', which inserts the row but
 * never advances the sequence. Any insert from the application then collided
 * with the primary key. Matching by name lets the database assign the id.
 */
class SeederIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function seedAll(): void
    {
        $this->seed(\Database\Seeders\CostCenterSeeder::class);
        $this->seed(\Database\Seeders\FinancialCategorySeeder::class);
        $this->seed(\Database\Seeders\FinancialAccountSeeder::class);
        $this->seed(\Database\Seeders\SupplierSeeder::class);
        $this->seed(\Database\Seeders\TrainerSeeder::class);
    }

    public function test_seeders_do_not_duplicate_rows_when_reexecuted(): void
    {
        $this->seedAll();
        $this->seedAll();

        $this->assertDatabaseCount('cost_centers', 6);
        $this->assertDatabaseCount('financial_accounts', 1);
        $this->assertDatabaseCount('suppliers', 1);
        $this->assertDatabaseCount('trainers', 1);
    }

    public function test_financial_categories_point_to_the_matching_cost_center(): void
    {
        $this->seedAll();

        $receitas = CostCenter::query()->where('name', 'Receitas')->firstOrFail();
        $financeiras = CostCenter::query()->where('name', 'Despesas Financeiras')->firstOrFail();

        $this->assertDatabaseHas('financial_categories', [
            'name' => 'Venda de Produtos',
            'cost_center_id' => $receitas->id,
        ]);
        $this->assertDatabaseHas('financial_categories', [
            'name' => 'Outras Despesas',
            'cost_center_id' => $financeiras->id,
        ]);
        $this->assertDatabaseCount('financial_categories', 23);
    }

    /**
     * The regression that matters: the sequence must be able to hand out a fresh
     * id after seeding, otherwise every insert from the application fails.
     */
    public function test_sequences_allow_inserts_from_the_application(): void
    {
        $this->seedAll();

        $costCenter = CostCenter::query()->create([
            'name' => 'Centro Novo',
            'color' => '#123456',
            'operation_type' => OperationType::PAYABLE->value,
        ]);

        $this->assertNotSame(0, $costCenter->id);

        $supplier = Supplier::query()->create([
            'name' => 'Fornecedor Novo',
            'document' => '11111111111111',
            'phone' => '99999999999',
        ]);

        $this->assertNotSame(0, $supplier->id);

        $account = FinancialAccount::query()->create([
            'name' => 'Conta Nova',
            'account_type' => FinancialAccountType::CASH->value,
        ]);

        $this->assertNotSame(0, $account->id);

        $trainer = Trainer::query()->create([
            'name' => 'Treinador Novo',
            'document' => '22222222222',
            'phone' => '99999999999',
            'gender' => GenderType::MALE->value,
        ]);

        $this->assertNotSame(0, $trainer->id);
    }

    /**
 * @return array<string, array<string, mixed>>
 */
private function probeRow(string $table): array
{
    return match ($table) {
        'cost_centers' => [
            'name' => 'Probe', 'color' => '#000000',
            'operation_type' => OperationType::PAYABLE->value, 'visibility' => 'visible',
        ],
        'financial_accounts' => [
            'name' => 'Probe', 'account_type' => FinancialAccountType::CASH->value,
            'visibility' => 'visible',
        ],
        'suppliers' => [
            'name' => 'Probe', 'document' => '99999999999999',
            'phone' => '99999999999', 'visibility' => 'visible',
        ],
        'trainers' => [
            'name' => 'Probe', 'document' => '99999999999', 'phone' => '99999999999',
            'gender' => GenderType::MALE->value, 'visibility' => 'visible',
        ],
    };
}

public function test_sequence_hands_out_the_next_id_after_seeding(): void
    {
        $this->seedAll();

        foreach (['cost_centers', 'financial_accounts', 'suppliers', 'trainers'] as $table) {
            $expectedId = (int) DB::table($table)->max('id') + 1;

            $newId = DB::table($table)->insertGetId($this->probeRow($table));

            $this->assertSame(
                $expectedId,
                (int) $newId,
                "A sequence de {$table} nao handout o proximo id apos o seeding.",
            );

            DB::table($table)->where('id', $newId)->delete();
        }
    }

    public function test_reexecution_keeps_the_same_ids(): void
    {
        $this->seedAll();

        $before = FinancialCategory::query()->pluck('cost_center_id', 'name')->all();

        $this->seedAll();

        $this->assertSame($before, FinancialCategory::query()->pluck('cost_center_id', 'name')->all());
    }
}