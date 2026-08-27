<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: transactions
     * -----------------------------------------------------------------
     * One row per sale (the "receipt header"). Line items live in
     * `transaction_items`. This is the busiest table in the system —
     * expect it to grow the fastest, so it gets the most indexes.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('outlet_id')
                ->constrained('outlets')
                ->cascadeOnDelete();

            $table->foreignUuid('cashier_id')
                ->constrained('users');

            $table->foreignUuid('member_id')
                ->nullable()
                ->constrained('members')
                ->nullOnDelete();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);

            $table->string('payment_method', 30)
                ->comment('cash | midtrans | xendit');
            $table->string('payment_status', 20)->default('pending')
                ->comment('paid | pending | failed');
            $table->string('status', 20)->default('completed')
                ->comment('completed | voided');

            $table->foreignUuid('voided_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('voided_reason')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // INDEX: speeds up "list transactions for this outlet" (cashier
            // history screen) — one of the most frequent queries in the app.
            $table->index('outlet_id');

            // INDEX: speeds up date-range report queries (daily/monthly sales),
            // which filter and sort by created_at constantly.
            $table->index('created_at');

            // INDEX: speeds up "how many sales has this cashier processed today?"
            $table->index('cashier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};