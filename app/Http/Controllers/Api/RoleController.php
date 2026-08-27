<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService
    ) {}

    /**
     * Display a listing of roles and available permissions for the tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $roles = $this->roleService->getRoles($tenantId);
        $availablePermissions = Permission::all();

        return response()->json([
            'success' => true,
            'data' => [
                'roles' => $roles,
                'available_permissions' => $availablePermissions,
            ],
        ]);
    }

    /**
     * Store a newly created custom role.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $tenantId = $request->user()->tenant_id;
        $role = $this->roleService->createRole($tenantId, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil dibuat.',
            'data' => $role,
        ], 201);
    }

    /**
     * Update specified role and its assigned permissions.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        // Enforce multi-tenant scoping
        if ($role->tenant_id !== $request->user()->tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke role ini.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:50',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $updatedRole = $this->roleService->updateRole($role, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil diperbarui.',
            'data' => $updatedRole,
        ]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Request $request, Role $role): JsonResponse
    {
        if ($role->tenant_id !== $request->user()->tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke role ini.',
            ], 403);
        }

        $this->roleService->deleteRole($role);

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil dihapus (soft delete).',
        ]);
    }

    /**
     * Restore a soft-deleted custom role.
     */
    public function restore(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $restoredRole = $this->roleService->restoreRole($id, $tenantId);

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil dipulihkan.',
            'data' => $restoredRole,
        ]);
    }
}
