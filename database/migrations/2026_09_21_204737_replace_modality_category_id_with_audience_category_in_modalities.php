<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modalities', function (Blueprint $table) {
            $table->string('audience_category', 10)->nullable()->after('color');
        });

        DB::statement('
            UPDATE modalities
            SET audience_category = mc.audience
            FROM modality_categories mc
            WHERE modalities.modality_category_id = mc.id
        ');

        Schema::table('modalities', function (Blueprint $table) {
            $table->dropForeign(['modality_category_id']);
            $table->dropColumn('modality_category_id');
        });

        Schema::dropIfExists('modality_categories');
    }

    public function down(): void
    {
        Schema::create('modality_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('audience', 10);
            $table->timestamps();
            $table->unique('name');
        });

        DB::table('modality_categories')->insert([
            ['name' => 'Adulto', 'audience' => 'adult'],
            ['name' => 'Infantil', 'audience' => 'child'],
        ]);

        Schema::table('modalities', function (Blueprint $table) {
            $table->foreignId('modality_category_id')->nullable()->after('color');
        });

        DB::statement('
            UPDATE modalities
            SET modality_category_id = mc.id
            FROM modality_categories mc
            WHERE modalities.audience_category = mc.audience
        ');

        Schema::table('modalities', function (Blueprint $table) {
            $table->dropColumn('audience_category');
        });
    }
};
