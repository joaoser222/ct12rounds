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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('operation_type');
            $table->string('invoice_type');
            $table->date('due_date');
            $table->date('payment_date')->nullable();
            $table->string('payment_method', 20);
            $table->string('external_reference', 50)->nullable();
            $table->decimal('gross_value', 13, 4)->default(0);
            $table->decimal('discount_value', 13, 4)->default(0);
            $table->decimal('interest_value', 13, 4)->default(0);
            $table->decimal('fine_value', 13, 4)->default(0);
            $table->decimal('total', 13, 4)->storedAs('ROUND((gross_value - discount_value) + interest_value + fine_value,4)');
            $table->decimal('paid_value', 13, 4)->default(0);
            $table->integer('installment_number')->default(1);
            $table->string('status', 20);
            $table->string('annotations', 500)->nullable();
            $table->string('visibility', 10);
            // Relacionamentos
            $table->morphs('holder');
            $table->nullableMorphs('billable');
            $table->foreignId('financial_account_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('financial_category_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
