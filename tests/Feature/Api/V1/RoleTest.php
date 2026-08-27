<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owner can list and create custom roles with permissions', function () {
    // 1. Register owner
    $reg = $this->postJson('/api/auth/register-owner', [
        'business_name' => 'Fashion Boutique Bali',
        'full_name' => 'Sarah Johnson',
        'email' => 'sarah@boutique.com',
        'password' => 'password123',
    ]);

    $token = $reg->json('data.token');

    // Create a dummy permission
    $perm = Permission::create([
        'code' => 'product.void',
        'module' => 'product',
        'description' => 'Void product',
    ]);

    // 2. Create Custom Role
    $roleResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/roles', [
            'name' => 'Supervisor Kasir',
            'permission_ids' => [$perm->id],
        ]);

    $roleResponse->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Supervisor Kasir');

    // 3. List roles
    $listResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/roles');

    $listResponse->assertStatus(200)
        ->assertJsonPath('success', true);
});

test('cannot delete system default role', function () {
    $reg = $this->postJson('/api/auth/register-owner', [
        'business_name' => 'Fashion Boutique Bali',
        'full_name' => 'Sarah Johnson',
        'email' => 'sarah@boutique.com',
        'password' => 'password123',
    ]);

    $token = $reg->json('data.token');
    $ownerRoleId = $reg->json('data.user.role.id');

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson("/api/roles/{$ownerRoleId}");

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['role']);
});
