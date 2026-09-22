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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('email', 255)->nullable();
            $table->string('document', 11)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone', 11);
            $table->string('gender')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('address', 200)->nullable();
            $table->string('address_number', 10)->nullable();
            $table->string('address_complement', 100)->nullable();
            $table->string('address_state', 2)->nullable();
            $table->string('address_city', 100)->nullable();
            $table->string('address_district', 100)->nullable();
            $table->string('address_postal_code', 8)->nullable();
            $table->string('audience_category', 10)->nullable()->after('profile_image');
            $table->string('legal_representative_name', 255)->nullable();
            $table->string('legal_representative_document', 11)->nullable();
            $table->date('legal_representative_birth_date')->nullable();
            $table->foreignId('trainer_id')->nullable()->constrained()->onDelete('set null');
            $table->string('status', 10);
            $table->string('client_source', 20)->nullable()->index();
            $table->string('visibility', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
