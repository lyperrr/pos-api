<?php

namespace App\Services;

use App\Models\Outlet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class OutletService
{
    /**
     * Get all outlets for a tenant with optional search query.
     */
    public function getOutlets(string $tenantId, ?string $search = null): Collection
    {
        $query = Outlet::withTrashed()
            ->where('tenant_id', $tenantId);

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Create a new outlet branch for the tenant.
     */
    public function createOutlet(string $tenantId, array $data): Outlet
    {
        return Outlet::create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Update an existing outlet.
     */
    public function updateOutlet(Outlet $outlet, array $data): Outlet
    {
        $outlet->update([
            'name' => $data['name'] ?? $outlet->name,
            'address' => array_key_exists('address', $data) ? $data['address'] : $outlet->address,
            'phone' => array_key_exists('phone', $data) ? $data['phone'] : $outlet->phone,
            'is_active' => array_key_exists('is_active', $data) ? $data['is_active'] : $outlet->is_active,
        ]);

        return $outlet->fresh();
    }

    /**
     * Soft delete an outlet.
     */
    public function deleteOutlet(Outlet $outlet): bool
    {
        return (bool) $outlet->delete();
    }

    /**
     * Restore a soft deleted outlet.
     */
    public function restoreOutlet(string $id, string $tenantId): Outlet
    {
        $outlet = Outlet::onlyTrashed()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $outlet) {
            throw ValidationException::withMessages([
                'outlet' => ['Outlet tidak ditemukan atau tidak dalam status terhapus.'],
            ]);
        }

        $outlet->restore();

        return $outlet;
    }
}
