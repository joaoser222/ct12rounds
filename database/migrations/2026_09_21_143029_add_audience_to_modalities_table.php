<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modalities', function (Blueprint $table) {
            $table->foreignId('modality_category_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('modalities', function (Blueprint $table) {
            $table->dropForeign(['modality_category_id']);
            $table->dropColumn('modality_category_id');
        });
    }
};
