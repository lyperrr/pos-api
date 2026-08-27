<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: members
     * -----------------------------------------------------------------
     * Loyalty/membership records for a tenant's customers (not to be
     * confused with `users`, which are staff/system accounts).
     * Used for the loyalty-points feature (Phase 2).
     */
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->string('full_name', 150);
            $table->string('phone', 20)->nullable();
            $table->integer('total_points')->default(0);

            $table->timestamp('created_at')->useCurrent();

            // INDEX: speeds up member lookup by phone number at checkout.
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};