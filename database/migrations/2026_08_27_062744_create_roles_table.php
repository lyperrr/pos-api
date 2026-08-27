<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: roles
     * -----------------------------------------------------------------
     * Unlike `permissions`, roles ARE freely created/edited by the
     * tenant owner (via the "Manage Roles" screen). This is what makes
     * access control dynamic: a tenant can rename, clone, or invent
     * new roles (e.g. "Supervisor") and choose exactly which
     * permissions each one has.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // NULL tenant_id = a platform-level system role (e.g. Super Admin).
            // Non-null = a role that belongs to (and is editable by) that tenant.
            $table->foreignUuid('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->string('name', 50)
                ->comment('e.g. Owner, Manager, Cashier, or any custom name');

            // Marks built-in roles created automatically when a tenant signs
            // up, so the UI can prevent accidental deletion of the last Owner role.
            $table->boolean('is_system_default')->default(false);

            $table->timestamp('created_at')->useCurrent();

            // INDEX: speeds up "list all roles belonging to this tenant"
            // (shown every time the Manage Roles / Add User screen loads).
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};