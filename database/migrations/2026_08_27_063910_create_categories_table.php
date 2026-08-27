<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: categories
     * -----------------------------------------------------------------
     * Product grouping, scoped per tenant (e.g. "Beverages", "Tops",
     * "Souvenirs"). Kept flat for MVP — a nullable parent_id can be
     * added later for sub-categories if needed.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->string('name', 100);

            // INDEX: speeds up "list categories for this tenant" (product form dropdown).
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};