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
        Schema::create('gateway_customers', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_reference_key');
            $table->morphs('holder');
            $table->foreignId('gateway_account_id')->constrained()->onDelete('cascade');
            $table->foreignId('gateway_postback_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateway_customers');
    }
};
