<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: transaction_promotions
     * -----------------------------------------------------------------
     * Records which promotion(s) were applied to a given transaction,
     * and how much discount each one contributed. Many-to-many because
     * a single sale could stack more than one promo (Phase 2).
     */
    public function up(): void
    {
        Schema::create('transaction_promotions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('transaction_id')
                ->constrained('transactions')
                ->cascadeOnDelete();

            $table->foreignUuid('promotion_id')
                ->constrained('promotions');

            $table->decimal('discount_value', 12, 2)->default(0);

            // INDEX: speeds up "which promotions were used, and how often" reporting query.
            $table->index('promotion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_promotions');
    }
};