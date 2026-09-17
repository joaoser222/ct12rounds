<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateway_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('gateway_reference_key')->nullable();
            $table->string('status', 30);
            $table->string('status_description')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('validation_code')->nullable();
            $table->text('service_description')->nullable();
            $table->text('observations')->nullable();
            $table->decimal('value', 13, 4)->nullable();
            $table->decimal('deductions', 13, 4)->nullable();
            $table->date('effective_date')->nullable();
            $table->text('pdf_url')->nullable();
            $table->text('xml_url')->nullable();
            $table->string('municipal_service_id')->nullable();
            $table->string('municipal_service_code')->nullable();
            $table->string('municipal_service_description')->nullable();
            $table->string('external_reference')->nullable();
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('gateway_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gateway_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['gateway_account_id', 'gateway_reference_key']);
            $table->unique(['invoice_id', 'gateway_payment_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_invoices');
    }
};
