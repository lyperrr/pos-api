<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: outlets
     * -----------------------------------------------------------------
     * One or more physical branches belonging to a tenant.
     * Stock, products, and transactions are always scoped to an outlet,
     * so a tenant with multiple branches never mixes their inventory.
     */
    public function up(): void
    {
        Schema::create('outlets', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete(); // deleting a tenant removes all its outlets

            $table->string('name', 150);
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamp('created_at')->useCurrent();

            // INDEX: speeds up "list all outlets for this tenant" — a very
            // common query (dashboard, outlet switcher, reports).
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlets');
    }
};