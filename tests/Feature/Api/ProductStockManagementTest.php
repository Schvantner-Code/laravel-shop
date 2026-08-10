<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->category = Category::factory()->create();
});

test('public product responses expose availability without exact stock', function () {
    $availableProduct = Product::factory()->create(['stock' => 5]);
    $unavailableProduct = Product::factory()->create(['stock' => 0]);

    $this->getJson("/api/v1/products/{$availableProduct->id}")
        ->assertOk()
        ->assertJsonPath('data.in_stock', true)
        ->assertJsonMissingPath('data.stock');

    $this->getJson("/api/v1/products/{$unavailableProduct->id}")
        ->assertOk()
        ->assertJsonPath('data.in_stock', false)
        ->assertJsonMissingPath('data.stock');
});

test('admins can create products with stock', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/v1/admin/products', productPayload($this->category, 25))
        ->assertCreated()
        ->assertJsonPath('data.stock', 25)
        ->assertJsonPath('data.in_stock', true);

    $this->assertDatabaseHas('products', [
        'category_id' => $this->category->id,
        'stock' => 25,
    ]);
});

test('product stock defaults to zero when omitted', function () {
    $missingStock = productPayload($this->category, 10);
    unset($missingStock['stock']);

    $this->actingAs($this->admin)
        ->postJson('/api/v1/admin/products', $missingStock)
        ->assertCreated()
        ->assertJsonPath('data.stock', 0)
        ->assertJsonPath('data.in_stock', false);

    $this->assertDatabaseHas('products', [
        'category_id' => $this->category->id,
        'stock' => 0,
    ]);
});

test('product stock cannot be negative and must be an integer', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/v1/admin/products', productPayload($this->category, -1))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('stock', 'error.details');

    $this->actingAs($this->admin)
        ->postJson('/api/v1/admin/products', productPayload($this->category, 2.5))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('stock', 'error.details');

    $this->actingAs($this->admin)
        ->postJson('/api/v1/admin/products', productPayload($this->category, 'many'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('stock', 'error.details');
});

test('admins can update exact product stock', function () {
    $product = Product::factory()->create([
        'category_id' => $this->category->id,
        'price' => 2500,
        'stock' => 10,
    ]);

    $this->actingAs($this->admin)
        ->patchJson("/api/v1/admin/products/{$product->id}", ['stock' => 0])
        ->assertOk()
        ->assertJsonPath('data.stock', 0)
        ->assertJsonPath('data.in_stock', false);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'category_id' => $this->category->id,
        'price' => 2500,
        'stock' => 0,
    ]);
});

/**
 * Invalid scalar types are accepted deliberately so request-validation tests can
 * send them unchanged instead of PHP coercing them before the request is built.
 *
 * @return array{category_id: int, name: array{en: string, sk: string}, price: int, stock: int|float|string, is_active: bool}
 */
function productPayload(Category $category, int|float|string $stock): array
{
    return [
        'category_id' => $category->id,
        'name' => ['en' => 'Stocked notebook', 'sk' => 'Naskladnený zošit'],
        'price' => 1250,
        'stock' => $stock,
        'is_active' => true,
    ];
}
