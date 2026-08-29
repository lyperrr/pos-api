<?php

use App\Models\Outlet;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::create([
        'business_name' => 'Kopi Senja Bali',
        'business_type' => 'fnb',
        'subscription_status' => 'active',
        'subscription_plan' => 'pro',
    ]);

    $this->ownerRole = Role::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Owner',
        'is_system_default' => true,
    ]);

    $this->cashierRole = Role::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Kasir',
        'is_system_default' => true,
    ]);

    $this->mainOutlet = Outlet::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Outlet Utama Denpasar',
        'address' => 'Jl. Teuku Umar No. 88, Denpasar',
        'phone' => '081234567890',
        'is_active' => true,
    ]);

    $this->ownerUser = User::create([
        'tenant_id' => $this->tenant->id,
        'outlet_id' => $this->mainOutlet->id,
        'role_id' => $this->ownerRole->id,
        'full_name' => 'Willy Permana (Owner)',
        'email' => 'owner@kopisenja.com',
        'password' => bcrypt('password123'),
        'is_active' => true,
    ]);

    $this->cashierUser = User::create([
        'tenant_id' => $this->tenant->id,
        'outlet_id' => $this->mainOutlet->id,
        'role_id' => $this->cashierRole->id,
        'full_name' => 'Kadek Kasir',
        'email' => 'kasir@kopisenja.com',
        'password' => bcrypt('password123'),
        'is_active' => true,
    ]);
});

test('owner can list outlets and create a new branch outlet', function () {
    Sanctum::actingAs($this->ownerUser);

    $getResponse = $this->getJson('/api/outlets');
    $getResponse->assertStatus(200)
        ->assertJsonPath('success', true);

    $postResponse = $this->postJson('/api/outlets', [
        'name' => 'Outlet Cabang Canggu',
        'address' => 'Jl. Pantai Batu Bolong No. 12, Canggu',
        'phone' => '089876543210',
        'is_active' => true,
    ]);

    $postResponse->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Outlet Cabang Canggu');

    $this->assertDatabaseHas('outlets', [
        'name' => 'Outlet Cabang Canggu',
        'tenant_id' => $this->tenant->id,
    ]);
});

test('owner can update and soft delete an outlet', function () {
    Sanctum::actingAs($this->ownerUser);

    $updateResponse = $this->putJson("/api/outlets/{$this->mainOutlet->id}", [
        'name' => 'Outlet Utama Denpasar (Renovated)',
    ]);

    $updateResponse->assertStatus(200)
        ->assertJsonPath('data.name', 'Outlet Utama Denpasar (Renovated)');

    $deleteResponse = $this->deleteJson("/api/outlets/{$this->mainOutlet->id}");
    $deleteResponse->assertStatus(200);

    $this->assertSoftDeleted('outlets', [
        'id' => $this->mainOutlet->id,
    ]);
});

test('non-owner user is forbidden from creating an outlet', function () {
    Sanctum::actingAs($this->cashierUser);

    $response = $this->postJson('/api/outlets', [
        'name' => 'Outlet Ilegal Kasir',
        'address' => 'Jl. Unauthorized',
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('success', false);
});
