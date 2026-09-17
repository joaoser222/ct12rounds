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
        Schema::create('direct_lessons', function (Blueprint $table) {
            $table->id();
            $table->date('lesson_date');
            $table->string('status', 20);
            $table->string('payment_method', 20)->default('cash');
            $table->string('visibility', 10);
            $table->decimal('price', 13, 4);
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('trainer_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direct_lessons');
    }
};
