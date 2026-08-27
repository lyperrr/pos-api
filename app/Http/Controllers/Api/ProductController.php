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
            'tenant_id' => 'required|uuid|exists:tenants,id',
            'outlet_id' => 'required|uuid|exists:outlets,id',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'name' => 'required|string|max:150',
            'image' => 'nullable|string|url',
            'barcode' => 'nullable|string|max:100',
            'base_price' => 'required|numeric|min:0',
            'is_special' => 'nullable|boolean',
        ]);

        $product = $this->productService->createProduct($validated);
        $product->variants()->create([
            'variant_name' => 'Default',
            'sku' => 'SKU-'.strtoupper(Str::random(8)),
            'additional_price' => 0.00,
        ]);

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product->load(['category', 'variants'])),
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
