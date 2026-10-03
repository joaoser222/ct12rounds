<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            StateSeeder::class,
            ProductUnitySeeder::class,
            BankSeeder::class,
            CostCenterSeeder::class,
            FinancialCategorySeeder::class,
            LoyaltyLevelSeeder::class,
            FinancialAccountSeeder::class,
            SupplierSeeder::class,
            TrainerSeeder::class,
            ModalitySeeder::class,
            ClassScheduleSeeder::class,
            PlanCategorySeeder::class,
            PlanSeeder::class,
            SettingSeeder::class,
            ReportSeeder::class,
        ]);

        // Roles, permissions and the default role of every user are owned by
        // this command, so the seeded administrator ends up with access.
        Artisan::call('access-control:sync');
    }
}
