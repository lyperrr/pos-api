<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    /**
     * Fetch products for POS catalogue with server-side filtering & pagination.
     */
    public function getFilteredProducts(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['category', 'variants.stocks'])
            ->where('is_active', true);

        if (! empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (! empty($filters['outlet_id'])) {
            $query->where('outlet_id', $filters['outlet_id']);
        }

        // Category Filter
        if (! empty($filters['category_id']) && $filters['category_id'] !== 'all') {
            if ($filters['category_id'] === 'special') {
                $query->where('is_special', true);
            } else {
                $query->where(function ($q) use ($filters) {
                    $q->where('category_id', $filters['category_id'])
                        ->orWhereHas('category', function ($catQuery) use ($filters) {
                            $catQuery->where('slug', $filters['category_id']);
                        });
                });
            }
        }

        // Server-side search filter (with debounce from frontend)
        if (! empty($filters['search'])) {
            $search = '%'.strtolower(trim($filters['search'])).'%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(barcode) LIKE ?', [$search])
                    ->orWhereHas('category', function ($catQ) use ($search) {
                        $catQ->whereRaw('LOWER(name) LIKE ?', [$search]);
                    });
            });
        }

        return $query->latest('created_at')->paginate($perPage);
    }

    /**
     * Create product record.
     */
    public function createProduct(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * Soft delete product record.
     */
    public function deleteProduct(Product $product): void
    {
        $product->delete();
    }

    /**
     * Restore soft-deleted product record.
     */
    public function restoreProduct(string $id): Product
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->restore();

        return $product;
    }
}
