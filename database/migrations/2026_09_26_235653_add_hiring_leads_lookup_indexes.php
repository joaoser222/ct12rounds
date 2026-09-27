<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * hiring_leads only carried its primary key, so every lookup in the public
     * registration flow and in the access checks fell back to a sequential
     * scan. These are the columns the queries actually filter on.
     */
    public function up(): void
    {
        Schema::table('hiring_leads', function (Blueprint $table): void {
            $table->index('contract_id');
            $table->index(['email', 'phone']);
            $table->index('document');
        });
    }

    public function down(): void
    {
        Schema::table('hiring_leads', function (Blueprint $table): void {
            $table->dropIndex(['email', 'phone']);
            $table->dropIndex('document');
            $table->dropIndex('contract_id');
        });
    }
};
