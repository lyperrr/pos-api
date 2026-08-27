<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: product_variants
     * -----------------------------------------------------------------
     * A sellable variation of a product, e.g. "Red - L" for a t-shirt.
     * If a product has no real variants, still create ONE default
     * variant row for it — this keeps `stocks` and `transaction_items`
     * consistent (they always point to a variant, never to a product
     * directly).
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->string('variant_name', 100)
                ->comment('e.g. "Red - L", or "Default" if the product has no variants');

            $table->string('sku', 100)->unique();

            // Price added on top of the parent product's base_price
            $table->decimal('additional_price', 12, 2)->default(0);

            // INDEX: speeds up "get all variants of this product" (product edit screen).
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};