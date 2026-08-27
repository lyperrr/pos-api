<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: stocks
     * -----------------------------------------------------------------
     * Tracks quantity on hand for a specific variant, at a specific
     * outlet. This is what gets decremented automatically every time
     * a transaction is completed.
     */
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->foreignUuid('outlet_id')
                ->constrained('outlets')
                ->cascadeOnDelete();

            $table->integer('quantity')->default(0);

            // Used to trigger "low stock" notifications/badges in the UI.
            $table->integer('min_stock_alert')->default(0);

            $table->timestamp('updated_at')->useCurrent();

            // A variant should only have ONE stock row per outlet.
            // This also acts as the index used every time the POS screen
            // checks "is this item still available at this outlet?".
            $table->unique(['product_variant_id', 'outlet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};