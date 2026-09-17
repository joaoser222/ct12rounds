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
        Schema::create('gateway_postbacks', function (Blueprint $table) {
            $table->id();
            $table->string('postback_event');
            $table->string('postback_type');
            $table->string('external_event_key')->nullable();
            $table->json('payload');
            $table->string('status', 20);
            $table->foreignId('gateway_account_id')->constrained()->onDelete('cascade');
            $table->unique(['gateway_account_id', 'external_event_key']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateway_postbacks');
    }
};
