<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hiring_leads', function (Blueprint $table) {
            $table->string('payment_method', 20)->nullable()->after('source');
            $table->boolean('legal_representative')->default(false)->after('payment_method');
            $table->string('legal_representative_name', 255)->nullable()->after('legal_representative');
            $table->string('legal_representative_document', 11)->nullable()->after('legal_representative_name');
            $table->date('legal_representative_birth_date')->nullable()->after('legal_representative_document');
        });
    }

    public function down(): void
    {
        Schema::table('hiring_leads', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'legal_representative',
                'legal_representative_name',
                'legal_representative_document',
                'legal_representative_birth_date',
            ]);
        });
    }
};
