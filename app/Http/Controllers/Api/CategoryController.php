<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->query('tenant_id');

        $query = Category::query();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $categories = $query->get();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|uuid|exists:tenants,id',
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100',
            'icon' => 'nullable|string|max:50',
        ]);

        $category = Category::create($validated);

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ], 201);
    }
}
