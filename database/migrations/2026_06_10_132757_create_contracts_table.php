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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('plan_name');
            $table->string('visibility', 10);
            $table->string('status', 10);
            $table->string('accepted_terms', 255);
            $table->string('annotations', 500)->nullable();
            $table->foreignId('coupon_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('plan_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('gross_value', 13, 4)->nullable();
            $table->decimal('discount_value', 13, 4)->default(0);
            $table->decimal('total', 13, 4)->nullable();
            $table->string('payment_method', 20)->nullable();
            $table->date('first_due_date')->nullable();
            $table->unsignedInteger('installments')->nullable();
            $table->string('registration_token', 64)->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
