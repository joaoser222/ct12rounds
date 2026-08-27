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
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('registration_token', 64)->nullable()->unique()->after('installments');
            $table->foreignId('client_id')->nullable()->change();
        });

        Schema::table('hiring_leads', function (Blueprint $table) {
            $table->foreignId('contract_id')->nullable()->after('coupon_id')->constrained('contracts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hiring_leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contract_id');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropUnique(['registration_token']);
            $table->dropColumn('registration_token');
        });
    }
};