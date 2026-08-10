<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;

// Setup: Run before every test in this file
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('public users can list products', function () {
    Product::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/products');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('localization returns correct language', function () {
    Product::factory()->create([
        'name' => ['en' => 'Shoes', 'sk' => 'Topánky'],
    ]);

    $response = $this->getJson('/api/v1/products', ['Accept-Language' => 'sk']);

    $response->assertStatus(200)
        ->assertJsonPath('data.0.name', 'Topánky');

    $responseEn = $this->getJson('/api/v1/products', ['Accept-Language' => 'en']);

    $responseEn->assertStatus(200)
        ->assertJsonPath('data.0.name', 'Shoes');
});

test('admin can create products', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $payload = [
        'category_id' => Category::factory()->create()->id,
        'name' => ['en' => 'New Prod', 'sk' => 'Novy Prod'],
        'price' => 1000,
        'stock' => 20,
        'is_active' => true,
    ];

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/admin/products', $payload);

    $response->assertStatus(201);
    $this->assertDatabaseHas('products', [
        'price' => 1000,
        'stock' => 20,
    ]);
});

test('customer cannot create products', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $category = Category::factory()->create();

    $response = $this->actingAs($customer)
        ->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name' => ['en' => 'Hacker Product', 'sk' => 'Hacker'],
            'price' => 500,
            'stock' => 10,
            'is_active' => true,
        ]);

    $response->assertStatus(403);
});
