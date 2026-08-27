<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: products
     * -----------------------------------------------------------------
     * Base product record (the "parent" of any size/color variants).
     * Scoped to both tenant AND outlet, since the same tenant's
     * branches manage their own catalog/stock independently.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignUuid('outlet_id')
                ->constrained('outlets')
                ->cascadeOnDelete();

            $table->foreignUuid('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->string('name', 150);
            $table->string('barcode', 100)->nullable();
            $table->decimal('base_price', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamp('created_at')->useCurrent();

            // INDEX: the most frequent query in the whole system — "show me
            // the product catalog for outlet X of tenant Y" (POS screen,
            // product management screen). Composite index keeps this fast
            // even with thousands of products.
            $table->index(['tenant_id', 'outlet_id']);

            // INDEX: fast barcode lookup when scanning at checkout.
            $table->index('barcode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};