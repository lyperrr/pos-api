<?php

use App\Models\Outlet;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\TastyStationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(TastyStationSeeder::class);
});

test('it creates a transaction and deducts stock atomically', function () {
    $outlet = Outlet::first();
    $cashier = User::first();
    $variant = ProductVariant::first();

    $payload = [
        'outlet_id' => $outlet->id,
        'cashier_id' => $cashier->id,
        'order_number' => 'FO099',
        'table_number' => '05',
        'people_count' => 2,
        'order_type' => 'dine_in',
        'tax_amount' => 4.00,
        'donation_amount' => 1.00,
        'payment_method' => 'card',
        'items' => [
            [
                'product_variant_id' => $variant->id,
                'quantity' => 2,
                'unit_price' => 15.00,
            ],
        ],
    ];

    $response = $this->postJson('/api/transactions', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.order_number', 'FO099')
        ->assertJsonPath('data.subtotal', 30)
        ->assertJsonPath('data.total_amount', 35);

    $this->assertDatabaseHas('transactions', [
        'order_number' => 'FO099',
        'table_number' => '05',
    ]);
});
