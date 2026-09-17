<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color', 7);
            $table->string('operation_type', 20);
            $table->string('visibility', 20);
            $table->foreignId('cost_center_id')->nullable()->constrained()->onDelete('set null');
            $table->unique(['name', 'cost_center_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_categories');
    }
};
