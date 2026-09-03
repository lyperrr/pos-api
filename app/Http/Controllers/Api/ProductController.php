<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    /**
     * Display a listing of products with server-side filter & pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['tenant_id', 'outlet_id', 'category_id', 'search']);
        $perPage = (int) $request->query('per_page', 20);

        $paginated = $this->productService->getFilteredProducts($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($paginated->items()),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'nullable|uuid|exists:tenants,id',
            'outlet_id' => 'nullable|uuid|exists:outlets,id',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'name' => 'required|string|max:150',
            'image' => 'nullable|string',
            'barcode' => 'nullable|string|max:100',
            'sku' => 'nullable|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'base_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'is_special' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $tenantId = $validated['tenant_id'] ?? $request->user()?->tenant_id ?? \App\Models\Tenant::first()?->id;
        $outletId = $validated['outlet_id'] ?? $request->user()?->outlet_id ?? \App\Models\Outlet::first()?->id;

        // Auto-generate barcode on backend (GS1 Retail Internal Prefix 200) if empty/null
        $barcode = !empty($validated['barcode'])
            ? $validated['barcode']
            : '200' . str_pad((string) mt_rand(1, 999999999), 9, '0', STR_PAD_LEFT);

        // Auto-generate SKU on backend (POS Industry Structured Standard: KAT-INIT-XXXX) if empty/null
        if (!empty($validated['sku'])) {
            $sku = strtoupper($validated['sku']);
        } else {
            $catPrefix = 'GEN';
            if (!empty($validated['category_id'])) {
                $category = \App\Models\Category::find($validated['category_id']);
                if ($category && $category->name) {
                    $catPrefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $category->name), 0, 3)) ?: 'GEN';
                }
            }

            $nameWords = preg_split('/\s+/', strtoupper(trim($validated['name'])));
            $nameInitials = '';
            foreach ($nameWords as $w) {
                if ($w !== '') {
                    $nameInitials .= $w[0];
                }
            }
            $nameInitials = substr($nameInitials, 0, 4) ?: 'PRD';
            $randomNum = str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $sku = "{$catPrefix}-{$nameInitials}-{$randomNum}";
        }

        $basePrice = $validated['price'] ?? $validated['base_price'] ?? 0;

        $productData = [
            'tenant_id' => $tenantId,
            'outlet_id' => $outletId,
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'image' => $validated['image'] ?? null,
            'barcode' => $barcode,
            'base_price' => $basePrice,
            'is_special' => $validated['is_special'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'created_at' => now(),
        ];

        $product = $this->productService->createProduct($productData);

        // Create default variant with unique SKU
        $variant = $product->variants()->create([
            'variant_name' => 'Default',
            'sku' => $sku,
            'additional_price' => 0.00,
        ]);

        // Create initial stock for outlet
        if ($outletId) {
            $variant->stocks()->create([
                'outlet_id' => $outletId,
                'quantity' => (int) ($validated['stock'] ?? 10),
                'min_stock_alert' => (int) ($validated['min_stock'] ?? 5),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product->load(['category', 'variants.stocks'])),
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new ProductResource($product->load(['category', 'variants.stocks'])),
        ]);
    }

    /**
     * Soft delete specified product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->productService->deleteProduct($product);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus (soft delete).',
        ]);
    }

    /**
     * Restore soft-deleted product.
     */
    public function restore(string $id): JsonResponse
    {
        $restoredProduct = $this->productService->restoreProduct($id);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dipulihkan.',
            'data' => new ProductResource($restoredProduct->load(['category', 'variants'])),
        ]);
    }
}
