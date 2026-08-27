<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owner can register a new business tenant and user', function () {
    $payload = [
        'business_name' => 'Kedai Kopi Malioboro',
        'business_type' => 'retail',
        'full_name' => 'Budi Santoso',
        'email' => 'budi@kedaikopi.com',
        'password' => 'password123',
        'phone' => '081234567890',
        'outlet_name' => 'Outlet Cabang Utama',
    ];

    $response = $this->postJson('/api/auth/register-owner', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'user' => [
                    'id',
                    'full_name',
                    'email',
                    'tenant' => ['id', 'business_name'],
                    'outlet' => ['id', 'name'],
                    'role' => ['id', 'name'],
                ],
            ],
        ]);

    $this->assertDatabaseHas('tenants', [
        'business_name' => 'Kedai Kopi Malioboro',
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'budi@kedaikopi.com',
        'full_name' => 'Budi Santoso',
    ]);
});

test('registered owner can login with valid credentials', function () {
    // 1. Register owner first
    $this->postJson('/api/auth/register-owner', [
        'business_name' => 'Toko Busana Bali',
        'full_name' => 'Wayan Sudarma',
        'email' => 'wayan@busanabali.com',
        'password' => 'secret1234',
    ]);

    // 2. Login
    $loginResponse = $this->postJson('/api/auth/login', [
        'email' => 'wayan@busanabali.com',
        'password' => 'secret1234',
    ]);

    $loginResponse->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.email', 'wayan@busanabali.com');
});

test('login fails with wrong password', function () {
    $this->postJson('/api/auth/register-owner', [
        'business_name' => 'Toko Busana Bali',
        'full_name' => 'Wayan Sudarma',
        'email' => 'wayan@busanabali.com',
        'password' => 'secret1234',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'wayan@busanabali.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('authenticated user can access profile me endpoint', function () {
    $reg = $this->postJson('/api/auth/register-owner', [
        'business_name' => 'Resto Sunset',
        'full_name' => 'Made Sukerta',
        'email' => 'made@sunset.com',
        'password' => 'secret1234',
    ]);

    $token = $reg->json('data.token');

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/auth/me');

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.email', 'made@sunset.com');
});

test('authenticated user can logout', function () {
    $reg = $this->postJson('/api/auth/register-owner', [
        'business_name' => 'Resto Sunset',
        'full_name' => 'Made Sukerta',
        'email' => 'made@sunset.com',
        'password' => 'secret1234',
    ]);

    $token = $reg->json('data.token');

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/auth/logout');

    $response->assertStatus(200)
        ->assertJsonPath('success', true);
});
