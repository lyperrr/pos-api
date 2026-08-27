<?php

use Database\Seeders\TastyStationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(TastyStationSeeder::class);
});

test('it lists product catalogue with category filter and search', function () {
    $response = $this->getJson('/api/products');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'name', 'category', 'category_label', 'price', 'image', 'is_special'],
            ],
            'meta' => ['current_page', 'total'],
        ]);

    expect(count($response->json('data')))->toBeGreaterThan(0);
});

test('it filters products by search query', function () {
    $response = $this->getJson('/api/products?search=salmon');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->not->toBeEmpty();
    expect($data[0]['name'])->toContain('Salmon');
});
