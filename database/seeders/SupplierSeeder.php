<?php

namespace Database\Seeders;

use App\Enums\Visibility;
use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Matched by name, never by id: the database assigns ids so the sequence
     * stays aligned with max(id) and inserts from the application cannot collide.
     */
    public function run(): void
    {
        Supplier::updateOrCreate(
            ['name' => 'Fornecedor Padrão'],
            [
                'document' => '00000000000000',
                'phone' => '99999999999',
                'visibility' => Visibility::VISIBLE->value,
            ],
        );
    }
}
