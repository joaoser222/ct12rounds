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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->decimal('total', 13, 4)->default(0);
            $table->decimal('gross_value', 13, 4)->default(0);
            $table->decimal('discount_value', 13, 4)->default(0);
            $table->string('visibility', 10);
            $table->string('status', 20);
            $table->string('payment_method', 20);
            $table->date('first_due_date')->nullable();
            $table->unsignedInteger('installments')->default(1);
            $table->string('annotations', 500)->nullable();
            $table->boolean('disable_stock')->default(false);
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
