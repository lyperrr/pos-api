<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Register a new tenant business owner.
     */
    public function registerOwner(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:100',
            'business_type' => 'nullable|string|max:50',
            'full_name' => 'required|string|max:100',
            'email' => 'required|string|email|max:150|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'outlet_name' => 'nullable|string|max:100',
            'outlet_address' => 'nullable|string|max:255',
        ]);

        $result = $this->authService->registerOwner($validated);

        return response()->json([
            'success' => true,
            'message' => 'Registrasi bisnis & owner berhasil.',
            'data' => $result,
        ], 201);
    }

    /**
     * Authenticate user with email and password.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $result = $this->authService->login($validated['email'], $validated['password']);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => $result,
        ]);
    }

    /**
     * Revoke current access token (Logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Get authenticated user profile & tenant info.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->me($request->user());

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }
}
