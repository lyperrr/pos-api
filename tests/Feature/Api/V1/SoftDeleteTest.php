<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('custom role can be soft deleted and restored', function () {
    $reg = $this->postJson('/api/auth/register-owner', [
        'business_name' => 'Resto Demo',
        'full_name' => 'Owner Demo',
        'email' => 'owner@demo.com',
        'password' => 'password123',
    ]);
    $token = $reg->json('data.token');
    $tenantId = $reg->json('data.user.tenant.id');

    $role = Role::create([
        'tenant_id' => $tenantId,
        'name' => 'Kasir Malam',
        'is_system_default' => false,
    ]);

    // 1. Soft Delete Role
    $delRes = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson("/api/roles/{$role->id}");
    $delRes->assertStatus(200);

    $this->assertSoftDeleted('roles', ['id' => $role->id]);

    // 2. Restore Role
    $resRes = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/roles/{$role->id}/restore");
    $resRes->assertStatus(200)
        ->assertJsonPath('data.name', 'Kasir Malam');

    $this->assertDatabaseHas('roles', ['id' => $role->id, 'deleted_at' => null]);
});

test('product can be soft deleted and restored', function () {
    $reg = $this->postJson('/api/auth/register-owner', [
        'business_name' => 'Resto Demo Product',
        'full_name' => 'Owner Demo',
        'email' => 'product@demo.com',
        'password' => 'password123',
    ]);
    $tenantId = $reg->json('data.user.tenant.id');
    $outletId = $reg->json('data.user.outlet.id');
    $category = Category::create(['tenant_id' => $tenantId, 'name' => 'Minuman', 'slug' => 'minuman']);

    $product = Product::create([
        'tenant_id' => $tenantId,
        'outlet_id' => $outletId,
        'category_id' => $category->id,
        'name' => 'Es Teh Manis',
        'base_price' => 5000,
        'is_active' => true,
    ]);

    // 1. Soft Delete Product
    $deleteResponse = $this->deleteJson("/api/products/{$product->id}");
    $deleteResponse->assertStatus(200);

    $this->assertSoftDeleted('products', ['id' => $product->id]);

    // 2. Restore Product
    $restoreResponse = $this->postJson("/api/products/{$product->id}/restore");
    $restoreResponse->assertStatus(200)
        ->assertJsonPath('data.name', 'Es Teh Manis');

    $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
});
