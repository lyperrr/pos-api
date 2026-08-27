<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: role_permissions
     * -----------------------------------------------------------------
     * Pivot/junction table: many-to-many between roles and permissions.
     * This is the actual "switchboard" the tenant owner toggles on the
     * Manage Roles screen (checkbox per permission, per role).
     */
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();

            $table->foreignUuid('permission_id')
                ->constrained('permissions')
                ->cascadeOnDelete();

            // Prevents the same permission being attached twice to one role,
            // and doubles as an index that speeds up permission-check queries
            // ("does this role have permission X?").
            $table->unique(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};