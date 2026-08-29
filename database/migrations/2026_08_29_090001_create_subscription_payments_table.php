<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: subscription_payments
     * Logs all tenant subscription payment orders via Midtrans / Xendit / Manual.
     */
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->string('invoice_number', 50)->unique();
            $table->string('plan_code', 30)->comment('starter | pro | enterprise');
            $table->string('billing_cycle', 20)->comment('monthly | yearly');
            $table->decimal('amount', 12, 2);

            $table->string('status', 20)
                ->default('pending')
                ->comment('pending | paid | failed | expired');

            $table->string('payment_gateway', 30)
                ->default('midtrans')
                ->comment('midtrans | xendit | manual');

            $table->string('payment_channel', 50)->nullable()->comment('gopay | bca_va | qris | credit_card');
            $table->string('gateway_transaction_id', 100)->nullable();
            $table->text('snap_token')->nullable();
            $table->text('payment_url')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('period_starts_at')->nullable();
            $table->timestamp('period_ends_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
