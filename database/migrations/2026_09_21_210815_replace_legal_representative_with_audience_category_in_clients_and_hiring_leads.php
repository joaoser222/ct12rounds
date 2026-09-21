<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Clients
        Schema::table('clients', function (Blueprint $table) {
            $table->string('audience_category', 10)->nullable()->after('profile_image');
        });

        DB::statement("
            UPDATE clients
            SET audience_category = CASE
                WHEN legal_representative = true THEN 'child'
                ELSE 'adult'
            END
        ");

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('legal_representative');
        });

        // Hiring Leads
        Schema::table('hiring_leads', function (Blueprint $table) {
            $table->string('audience_category', 10)->nullable()->after('email');
        });

        DB::statement("
            UPDATE hiring_leads
            SET audience_category = CASE
                WHEN legal_representative = true THEN 'child'
                ELSE 'adult'
            END
        ");

        Schema::table('hiring_leads', function (Blueprint $table) {
            $table->dropColumn('legal_representative');
        });
    }

    public function down(): void
    {
        // Clients
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('legal_representative')->default(false)->after('audience_category');
        });

        DB::statement("
            UPDATE clients
            SET legal_representative = (audience_category = 'child')
        ");

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('audience_category');
        });

        // Hiring Leads
        Schema::table('hiring_leads', function (Blueprint $table) {
            $table->boolean('legal_representative')->default(false)->after('audience_category');
        });

        DB::statement("
            UPDATE hiring_leads
            SET legal_representative = (audience_category = 'child')
        ");

        Schema::table('hiring_leads', function (Blueprint $table) {
            $table->dropColumn('audience_category');
        });
    }
};
