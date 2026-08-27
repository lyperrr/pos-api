<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: permissions
     * -----------------------------------------------------------------
     * Fixed master list of granular access rights, managed at the
     * SYSTEM level only (tenants cannot create/edit this list — they
     * can only pick which ones to attach to their own roles).
     *
     * Example rows: 'product.create', 'transaction.void', 'report.export'
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('code', 100)->unique()
                ->comment("Unique key used in code, e.g. 'product.create'");

            $table->string('module', 50)
                ->comment('product | stock | transaction | report | user | role | outlet | settings');

            $table->string('description', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};