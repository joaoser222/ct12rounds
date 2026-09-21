<?php

namespace Database\Seeders;

use App\Enums\Audience;
use App\Models\ModalityCategory;
use Illuminate\Database\Seeder;

class ModalityCategorySeeder extends Seeder
{
    public function run(): void
    {
        ModalityCategory::upsert([
            ['name' => 'Adulto', 'slug' => 'adulto', 'audience' => Audience::ADULT->value, 'visibility' => 'visible'],
            ['name' => 'Infantil', 'slug' => 'infantil', 'audience' => Audience::CHILD->value, 'visibility' => 'visible'],
        ], ['name'], ['slug', 'audience', 'visibility']);
    }
}
