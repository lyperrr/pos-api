<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TastyStationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Demo Tenant
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'business_name' => 'Tasty Station Restaurant',
            'business_type' => 'retail',
            'subscription_status' => 'active',
        ]);

        // 2. Create Demo Outlet
        $outlet = Outlet::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Main Outlet Nusa Dua',
            'address' => 'Kawasan Pariwisata Nusa Dua Lot NW-1, Bali',
            'phone' => '0361-900800',
            'is_active' => true,
        ]);

        // 3. Create Role & Cashier User
        $role = Role::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Admin',
            'is_system_default' => true,
        ]);

        $cashier = User::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'role_id' => $role->id,
            'full_name' => 'Ibrahim Kadri',
            'email' => 'ibrahim@tastystation.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // 4. Create Categories matching UI
        $categoriesData = [
            ['name' => 'All Menu', 'slug' => 'all', 'icon' => 'utensils'],
            ['name' => 'Special', 'slug' => 'special', 'icon' => 'sparkles'],
            ['name' => 'Soups', 'slug' => 'soups', 'icon' => 'soup'],
            ['name' => 'Desserts', 'slug' => 'desserts', 'icon' => 'cake'],
            ['name' => 'Chickens', 'slug' => 'chickens', 'icon' => 'drumstick'],
            ['name' => 'Lunch', 'slug' => 'lunch', 'icon' => 'utensils'],
            ['name' => 'Salad', 'slug' => 'salad', 'icon' => 'utensils'],
            ['name' => 'Pasta', 'slug' => 'pasta', 'icon' => 'utensils'],
            ['name' => 'Beef', 'slug' => 'beef', 'icon' => 'utensils'],
            ['name' => 'Rice', 'slug' => 'rice', 'icon' => 'utensils'],
        ];

        $categories = [];
        foreach ($categoriesData as $catData) {
            $categories[$catData['slug']] = Category::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenant->id,
                'name' => $catData['name'],
                'slug' => $catData['slug'],
                'icon' => $catData['icon'],
            ]);
        }

        // 5. Create Dishes matching UI design image
        $dishes = [
            [
                'name' => 'Grilled Salmon Steak',
                'category_slug' => 'lunch',
                'price' => 15.00,
                'image' => 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=500&auto=format&fit=crop&q=80',
                'is_special' => true,
            ],
            [
                'name' => 'Tofu Poke Bowl',
                'category_slug' => 'salad',
                'price' => 7.00,
                'image' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&auto=format&fit=crop&q=80',
                'is_special' => false,
            ],
            [
                'name' => 'Pasta with Roast Beef',
                'category_slug' => 'pasta',
                'price' => 10.00,
                'image' => 'https://images.unsplash.com/photo-1551183053-bf91a1d81141?w=500&auto=format&fit=crop&q=80',
                'is_special' => true,
            ],
            [
                'name' => 'Beef Steak',
                'category_slug' => 'beef',
                'price' => 30.00,
                'image' => 'https://images.unsplash.com/photo-1544025162-d76694265947?w=500&auto=format&fit=crop&q=80',
                'is_special' => true,
            ],
            [
                'name' => 'Shrimp Rice Bowl',
                'category_slug' => 'rice',
                'price' => 6.00,
                'image' => 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=500&auto=format&fit=crop&q=80',
                'is_special' => false,
            ],
            [
                'name' => 'Apple Stuffed Pancake',
                'category_slug' => 'desserts',
                'price' => 35.00,
                'image' => 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=500&auto=format&fit=crop&q=80',
                'is_special' => true,
            ],
            [
                'name' => 'Chicken Quinoa & Herbs',
                'category_slug' => 'chickens',
                'price' => 12.00,
                'image' => 'https://images.unsplash.com/photo-1543339308-43e59d6b73a6?w=500&auto=format&fit=crop&q=80',
                'is_special' => false,
            ],
            [
                'name' => 'Vegetable Shrimp',
                'category_slug' => 'salad',
                'price' => 10.00,
                'image' => 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=500&auto=format&fit=crop&q=80',
                'is_special' => false,
            ],
            [
                'name' => 'Creamy Mushroom Soup',
                'category_slug' => 'soups',
                'price' => 8.50,
                'image' => 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=500&auto=format&fit=crop&q=80',
                'is_special' => false,
            ],
        ];

        foreach ($dishes as $dish) {
            $prod = Product::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenant->id,
                'outlet_id' => $outlet->id,
                'category_id' => $categories[$dish['category_slug']]->id ?? null,
                'name' => $dish['name'],
                'image' => $dish['image'],
                'base_price' => $dish['price'],
                'is_special' => $dish['is_special'],
                'is_active' => true,
            ]);

            $variant = $prod->variants()->create([
                'id' => (string) Str::uuid(),
                'variant_name' => 'Default',
                'sku' => 'SKU-'.strtoupper(Str::slug($dish['name'])),
                'additional_price' => 0.00,
            ]);

            $variant->stocks()->create([
                'id' => (string) Str::uuid(),
                'outlet_id' => $outlet->id,
                'quantity' => 100,
                'min_stock_alert' => 10,
            ]);
        }

        // 6. Create Live Active Orders matching reference UI (#FO027, #FO028, #FO019)
        $liveOrders = [
            [
                'order_number' => 'FO027',
                'table_number' => '03',
                'people_count' => 4,
                'order_type' => 'dine_in',
                'subtotal' => 120.00,
                'tax_amount' => 7.20,
                'total_amount' => 128.20,
                'status' => 'completed',
            ],
            [
                'order_number' => 'FO028',
                'table_number' => '07',
                'people_count' => 2,
                'order_type' => 'wait_list',
                'subtotal' => 45.00,
                'tax_amount' => 2.70,
                'total_amount' => 48.70,
                'status' => 'completed',
            ],
            [
                'order_number' => 'FO019',
                'table_number' => '09',
                'people_count' => 3,
                'order_type' => 'take_away',
                'subtotal' => 30.00,
                'tax_amount' => 1.80,
                'total_amount' => 32.80,
                'status' => 'completed',
            ],
        ];

        foreach ($liveOrders as $lo) {
            Transaction::create([
                'id' => (string) Str::uuid(),
                'outlet_id' => $outlet->id,
                'cashier_id' => $cashier->id,
                'order_number' => $lo['order_number'],
                'table_number' => $lo['table_number'],
                'people_count' => $lo['people_count'],
                'order_type' => $lo['order_type'],
                'subtotal' => $lo['subtotal'],
                'tax_amount' => $lo['tax_amount'],
                'donation_amount' => 1.00,
                'total_amount' => $lo['total_amount'],
                'payment_method' => 'card',
                'payment_status' => 'paid',
                'status' => $lo['status'],
            ]);
        }
    }
}
