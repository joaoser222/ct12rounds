<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hiring_leads', function (Blueprint $table) {
            $table->string('document', 14)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('hiring_leads', function (Blueprint $table) {
            $table->string('document', 14)->nullable(false)->change();
        });
    }
};