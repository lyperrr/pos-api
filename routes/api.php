<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderLineController;
use App\Http\Controllers\Api\OutletController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API Routes
|--------------------------------------------------------------------------
*/

// Public Authentication & Business Owner Registration
Route::post('/auth/register-owner', [AuthController::class, 'registerOwner']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/verify-reset-token', [AuthController::class, 'verifyResetToken']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Protected Authenticated Routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // RBAC Roles & Permissions
    Route::post('/roles/{id}/restore', [RoleController::class, 'restore']);
    Route::apiResource('roles', RoleController::class);

    // Outlets (Owner-only for CUD)
    Route::post('/outlets/{id}/restore', [OutletController::class, 'restore']);
    Route::apiResource('outlets', OutletController::class);
});

// Categories
Route::get('/categories', [CategoryController::class, 'index']);
Route::post('/categories', [CategoryController::class, 'store']);

// Products (Server-side search, category filter, pagination)
Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::delete('/products/{product}', [ProductController::class, 'destroy']);
Route::post('/products/{id}/restore', [ProductController::class, 'restore']);

// Transactions (Checkout, Order history, Void)
Route::get('/transactions', [TransactionController::class, 'index']);
Route::post('/transactions', [TransactionController::class, 'store']);
Route::post('/transactions/{transaction}/void', [TransactionController::class, 'void']);

// Live Order Line Cards
Route::get('/orders/live', [OrderLineController::class, 'index']);
