<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: promotions
     * -----------------------------------------------------------------
     * Tenant-level discount/promo definitions (Phase 2 feature, schema
     * prepared now so nothing needs to be restructured later).
     */
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->string('name', 100);
            $table->string('type', 20)
                ->comment('percentage | fixed | bogo');
            $table->decimal('value', 12, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);

            // INDEX: speeds up "get currently active promotions for this tenant"
            // which is checked on every transaction at checkout.
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};