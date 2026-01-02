<?php

use App\Models\Product;
use App\Models\User;

// Setup: Run before every test in this file
beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

test('public users can list products', function () {
    Product::factory()->count(3)->create();

    $response = $this->getJson('/api/products');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('localization returns correct language', function () {
    Product::factory()->create([
        'name' => ['en' => 'Shoes', 'sk' => 'Topánky'],
    ]);

    $response = $this->getJson('/api/products', ['Accept-Language' => 'sk']);

    $response->assertStatus(200)
        ->assertJsonPath('data.0.name', 'Topánky');

    $responseEn = $this->getJson('/api/products', ['Accept-Language' => 'en']);

    $responseEn->assertStatus(200)
        ->assertJsonPath('data.0.name', 'Shoes');
});

test('admin can create products', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $payload = [
        'category_id' => \App\Models\Category::factory()->create()->id,
        'name' => ['en' => 'New Prod', 'sk' => 'Novy Prod'],
        'price' => 1000,
        'is_active' => true,
    ];

    $response = $this->actingAs($admin)
        ->postJson('/api/admin/products', $payload);

    $response->assertStatus(201);
    $this->assertDatabaseHas('products', [
        'price' => 1000,
    ]);
});

test('customer cannot create products', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $category = \App\Models\Category::factory()->create();

    $response = $this->actingAs($customer)
        ->postJson('/api/admin/products', [
            'category_id' => $category->id,
            'name' => ['en' => 'Hacker Product', 'sk' => 'Hacker'],
            'price' => 500,
            'is_active' => true,
        ]);

    $response->assertStatus(403);
});
