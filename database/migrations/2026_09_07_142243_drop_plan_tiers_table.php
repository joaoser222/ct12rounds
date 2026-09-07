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
        Schema::dropIfExists('plan_tiers');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('plan_tiers', function (Blueprint $table) {
            $table->id();
            $table->integer('quantity');
            $table->decimal('price', 13, 4);
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }
};
