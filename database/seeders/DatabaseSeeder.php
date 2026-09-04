<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UfSeeder::class,
            ProductUnitySeeder::class,
            BankSeeder::class,
            CostCenterSeeder::class,
            FinancialCategorySeeder::class,
            FinancialAccountSeeder::class,
            SupplierSeeder::class,
            TrainerSeeder::class,
            ModalitySeeder::class,
            PlanCategorySeeder::class,
            PlanSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
