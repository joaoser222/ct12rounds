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
        Schema::create('hiring_leads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('phone', 20);
            $table->string('document', 14);
            $table->string('gender', 1)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('address', 200)->nullable();
            $table->string('address_number', 10)->nullable();
            $table->string('address_complement', 100)->nullable();
            $table->string('address_district', 100)->nullable();
            $table->string('address_state', 2)->nullable();
            $table->string('address_city', 100)->nullable();
            $table->string('address_postal_code', 8)->nullable();
            $table->string('status', 20);
            $table->string('source', 20);
            $table->string('visibility', 10);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('plan_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('coupon_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hiring_leads');
    }
};
