<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modality_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('week_day');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('visibility', 10)->default('visible');
            $table->timestamps();

            $table->unique(['modality_id', 'week_day', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
