<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: users
     * -----------------------------------------------------------------
     * NOTE: this REPLACES Laravel's default users migration. Skip the
     * default one that ships with a fresh Laravel install (the
     * password_reset_tokens/sessions migrations can stay as-is).
     *
     * - Super Admin      -> tenant_id NULL, outlet_id NULL
     * - Owner            -> tenant_id set,  outlet_id NULL (access to all outlets)
     * - Manager/Cashier  -> tenant_id set, outlet_id set (scoped to one outlet)
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->cascadeOnDelete()
                ->comment('NULL for platform Super Admin');

            $table->foreignUuid('outlet_id')
                ->nullable()
                ->constrained('outlets')
                ->nullOnDelete() // if outlet is deleted, don't delete the user, just unassign
                ->comment('NULL if role has access to all outlets (e.g. Owner)');

            $table->foreignUuid('role_id')
                ->constrained('roles');

            $table->string('full_name', 150);
            $table->string('email', 150)->unique();
            $table->string('password'); // hashed via bcrypt/argon by Laravel
            $table->string('phone', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();

            $table->timestamps();

            // INDEX: speeds up login/lookup queries scoped to a tenant or outlet
            // (e.g. "list all cashiers in this outlet").
            $table->index('tenant_id');
            $table->index('outlet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};