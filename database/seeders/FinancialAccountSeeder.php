<?php

namespace Database\Seeders;

use App\Enums\FinancialAccountType;
use App\Enums\Visibility;
use App\Models\FinancialAccount;
use Illuminate\Database\Seeder;

class FinancialAccountSeeder extends Seeder
{
    /**
     * Matched by name, never by id: the database assigns ids so the sequence
     * stays aligned with max(id) and inserts from the application cannot collide.
     */
    public function run(): void
    {
        FinancialAccount::updateOrCreate(
            ['name' => 'Conta Caixa Padrão'],
            [
                'account_type' => FinancialAccountType::CASH->value,
                'balance' => 0,
                'visibility' => Visibility::VISIBLE->value,
            ],
        );
    }
}
