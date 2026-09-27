<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Scopes a coupon to one or more plans. A coupon with no row here applies
     * to every plan, which is what a one-off negotiated discount needs.
     */
    public function up(): void
    {
        Schema::create('coupon_plan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['coupon_id', 'plan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_plan');
    }
};
