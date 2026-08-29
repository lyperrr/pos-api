<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add subscription billing & cycle fields to tenants table.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('subscription_plan', 30)
                ->default('starter')
                ->after('business_type')
                ->comment('starter | pro | enterprise');

            $table->string('billing_cycle', 20)
                ->default('monthly')
                ->after('subscription_plan')
                ->comment('monthly | yearly');

            $table->timestamp('trial_ends_at')
                ->nullable()
                ->after('subscription_status')
                ->comment('14-day free trial end date');

            $table->timestamp('subscription_starts_at')
                ->nullable()
                ->after('trial_ends_at');

            $table->timestamp('subscription_expires_at')
                ->nullable()
                ->after('subscription_starts_at');

            $table->unsignedSmallInteger('max_outlets')
                ->default(1)
                ->after('subscription_expires_at')
                ->comment('Maximum outlets allowed under current plan');

            $table->unsignedSmallInteger('max_users')
                ->default(3)
                ->after('max_outlets')
                ->comment('Maximum staff accounts allowed under current plan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_plan',
                'billing_cycle',
                'trial_ends_at',
                'subscription_starts_at',
                'subscription_expires_at',
                'max_outlets',
                'max_users',
            ]);
        });
    }
};
