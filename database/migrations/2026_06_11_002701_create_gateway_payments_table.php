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
        Schema::create('gateway_payments', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_reference_key');
            $table->string('payment_method', 20);
            $table->date('payment_date')->nullable();
            $table->string('status', 20);
            $table->decimal('gross_value', 13, 4);
            $table->decimal('fee_value', 13, 4);
            $table->decimal('total', 13, 4)->storedAs('gross_value - fee_value');
            $table->foreignId('gateway_account_id')->constrained()->onDelete('cascade');
            $table->foreignId('gateway_customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('gateway_postback_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateway_payments');
    }
};
