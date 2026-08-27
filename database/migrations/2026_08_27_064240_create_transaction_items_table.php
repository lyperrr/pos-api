<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: transaction_items
     * -----------------------------------------------------------------
     * Line items ("receipt body") — one row per product variant sold
     * within a transaction. unit_price is stored (not just referenced
     * from the product) so historical receipts stay accurate even if
     * the product's price changes later.
     */
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('transaction_id')
                ->constrained('transactions')
                ->cascadeOnDelete();

            $table->foreignUuid('product_variant_id')
                ->constrained('product_variants');

            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);

            // INDEX: speeds up "load all line items for this receipt"
            // and "which products sell the most" report queries.
            $table->index('transaction_id');
            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};