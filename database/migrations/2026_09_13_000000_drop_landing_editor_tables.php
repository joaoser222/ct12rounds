<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('landing_admin_users');
        Schema::dropIfExists('landing_contents');
    }

    public function down(): void
    {
        // Re-creation is not supported after migration.
    }
};
