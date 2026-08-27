<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class RoleService
{
    /**
     * Get all roles belonging to a tenant (or default system roles).
     */
    public function getRoles(string $tenantId): Collection
    {
        return Role::with('permissions')
            ->where(function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                    ->orWhereNull('tenant_id');
            })
            ->get();
    }

    /**
     * Create a new custom role with permissions for a tenant.
     */
    public function createRole(string $tenantId, array $data): Role
    {
        $role = Role::create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'is_system_default' => false,
        ]);

        if (isset($data['permission_ids']) && is_array($data['permission_ids'])) {
            $role->permissions()->sync($data['permission_ids']);
        }

        return $role->load('permissions');
    }

    /**
     * Update a role and its attached permissions.
     */
    public function updateRole(Role $role, array $data): Role
    {
        if (isset($data['name'])) {
            $role->update(['name' => $data['name']]);
        }

        if (isset($data['permission_ids']) && is_array($data['permission_ids'])) {
            $role->permissions()->sync($data['permission_ids']);
        }

        return $role->load('permissions');
    }

    /**
     * Delete a custom role. Cannot delete system default roles.
     */
    public function deleteRole(Role $role): void
    {
        if ($role->is_system_default) {
            throw ValidationException::withMessages([
                'role' => ['Role bawaan sistem tidak dapat dihapus.'],
            ]);
        }

        $role->delete();
    }

    /**
     * Restore a soft-deleted custom role.
     */
    public function restoreRole(string $id, string $tenantId): Role
    {
        $role = Role::withTrashed()->where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();
        $role->restore();

        return $role->load('permissions');
    }
}
