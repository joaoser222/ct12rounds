<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gateway_credit_cards', function (Blueprint $table) {
            $table->foreignId('gateway_customer_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('gateway_credit_cards', function (Blueprint $table) {
            $table->foreignId('gateway_customer_id')->nullable(false)->change();
        });
    }
};
