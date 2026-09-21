<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->integer('min_months')->default(0);
            $table->string('color', 7)->nullable();
            $table->string('description', 500)->nullable();
            $table->string('visibility', 10)->default('visible');
            $table->timestamps();

            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_levels');
    }
};
