<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->string('phone', 13)->change();
            $table->string('legal_representative_document', 14)->nullable()->change();
        });

        Schema::table('hiring_leads', function (Blueprint $table): void {
            $table->string('document', 11)->nullable()->change();
            $table->string('legal_representative_document', 14)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('hiring_leads', function (Blueprint $table): void {
            $table->string('legal_representative_document', 11)->nullable()->change();
            $table->string('document', 14)->nullable()->change();
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->string('legal_representative_document', 11)->nullable()->change();
            $table->string('phone', 11)->change();
        });
    }
};
