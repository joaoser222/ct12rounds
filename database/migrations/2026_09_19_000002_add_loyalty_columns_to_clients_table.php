<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('loyalty_level_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedInteger('loyalty_streak_months')->default(0);
            $table->date('loyalty_since')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['loyalty_level_id']);
            $table->dropColumn(['loyalty_level_id', 'loyalty_streak_months', 'loyalty_since']);
        });
    }
};
