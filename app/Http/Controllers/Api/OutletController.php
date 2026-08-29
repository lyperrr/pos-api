<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Services\OutletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutletController extends Controller
{
    public function __construct(
        protected OutletService $outletService
    ) {}

    /**
     * Helper to verify if authenticated user has Owner role.
     */
    protected function authorizeOwner(Request $request): ?JsonResponse
    {
        $user = $request->user()->load('role');
        $roleName = strtolower($user->role?->name ?? '');

        if ($roleName !== 'owner') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Pemilik Usaha (Owner) yang berhak mengelola cabang outlet.',
            ], 403);
        }

        return null;
    }

    /**
     * List all outlets for authenticated tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $search = $request->query('search');

        $outlets = $this->outletService->getOutlets($tenantId, $search);

        return response()->json([
            'success' => true,
            'data' => $outlets,
        ]);
    }

    /**
     * Store a newly created outlet branch (Owner Only).
     */
    public function store(Request $request): JsonResponse
    {
        if ($forbidden = $this->authorizeOwner($request)) {
            return $forbidden;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $tenantId = $request->user()->tenant_id;
        $outlet = $this->outletService->createOutlet($tenantId, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Outlet cabang baru berhasil ditambahkan.',
            'data' => $outlet,
        ], 201);
    }

    /**
     * Display specified outlet details.
     */
    public function show(Request $request, Outlet $outlet): JsonResponse
    {
        if ($outlet->tenant_id !== $request->user()->tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke outlet ini.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $outlet,
        ]);
    }

    /**
     * Update an existing outlet (Owner Only).
     */
    public function update(Request $request, Outlet $outlet): JsonResponse
    {
        if ($forbidden = $this->authorizeOwner($request)) {
            return $forbidden;
        }

        if ($outlet->tenant_id !== $request->user()->tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke outlet ini.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        $updatedOutlet = $this->outletService->updateOutlet($outlet, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Informasi outlet berhasil diperbarui.',
            'data' => $updatedOutlet,
        ]);
    }

    /**
     * Soft delete specified outlet (Owner Only).
     */
    public function destroy(Request $request, Outlet $outlet): JsonResponse
    {
        if ($forbidden = $this->authorizeOwner($request)) {
            return $forbidden;
        }

        if ($outlet->tenant_id !== $request->user()->tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke outlet ini.',
            ], 403);
        }

        $this->outletService->deleteOutlet($outlet);

        return response()->json([
            'success' => true,
            'message' => 'Outlet berhasil dinonaktifkan / dihapus (soft delete).',
        ]);
    }

    /**
     * Restore soft-deleted outlet (Owner Only).
     */
    public function restore(Request $request, string $id): JsonResponse
    {
        if ($forbidden = $this->authorizeOwner($request)) {
            return $forbidden;
        }

        $tenantId = $request->user()->tenant_id;
        $outlet = $this->outletService->restoreOutlet($id, $tenantId);

        return response()->json([
            'success' => true,
            'message' => 'Outlet berhasil dipulihkan.',
            'data' => $outlet,
        ]);
    }
}
