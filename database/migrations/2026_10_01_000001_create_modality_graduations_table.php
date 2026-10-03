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
        Schema::create('modality_graduations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modality_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['modality_id', 'name']);
            $table->index(['modality_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modality_graduations');
    }
};
