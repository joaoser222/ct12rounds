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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('public_slug', 255)->nullable()->unique();
            $table->string('description', 500)->nullable();
            $table->decimal('price', 13, 4);
            $table->decimal('cancellation_fee', 13, 4)->nullable();
            $table->integer('duration_months');
            $table->string('audience', 20)->default('adult');
            $table->string('visibility', 10);
            $table->integer('modality_quantity')->default(1);
            $table->foreignId('plan_category_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
