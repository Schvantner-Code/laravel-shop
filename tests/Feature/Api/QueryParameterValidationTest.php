<?php

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('public pagination parameters must be within bounds', function () {
    $this->getJson('/api/v1/categories?page=0&per_page=51')
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonStructure(['error' => ['details' => ['page', 'per_page']]]);
});

test('catalog text filters have bounded lengths', function () {
    $this->getJson('/api/v1/categories?search='.str_repeat('a', 101))
        ->assertUnprocessable()
        ->assertJsonStructure(['error' => ['details' => ['search']]]);

    $this->getJson('/api/v1/products?category='.str_repeat('a', 256))
        ->assertUnprocessable()
        ->assertJsonStructure(['error' => ['details' => ['category']]]);
});

test('valid product pagination controls the response size', function () {
    Product::factory()->count(3)->create();

    $this->getJson('/api/v1/products?per_page=2&page=1')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.current_page', 1);
});

test('customer order pagination is validated', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)
        ->getJson('/api/v1/orders?per_page=0')
        ->assertUnprocessable()
        ->assertJsonStructure(['error' => ['details' => ['per_page']]]);
});

test('admin catalog scope is validated', function (string $endpoint) {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->getJson("/api/v1/admin/{$endpoint}?scope=unknown")
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonStructure(['error' => ['details' => ['scope']]]);
})->with(['categories', 'products']);

test('admin order filters sorting and pagination are validated', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/orders?user_id=0&product_id=nope&sort_by=id&sort_dir=sideways&page=0&per_page=51')
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonStructure([
            'error' => [
                'details' => ['user_id', 'product_id', 'sort_by', 'sort_dir', 'page', 'per_page'],
            ],
        ]);
});

test('admin orders preserve the default page size and accept a valid override', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/orders')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 20);

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/orders?per_page=5')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 5);
});

test('nonexistent numeric admin order filters return an empty list', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/orders?user_id=999999&product_id=999999')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
