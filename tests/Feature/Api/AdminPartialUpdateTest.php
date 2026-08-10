<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('admins can partially update a category', function () {
    $category = Category::factory()->create([
        'name' => ['en' => 'Notebooks', 'sk' => 'Zošity'],
        'slug' => 'notebooks',
    ]);

    $this->actingAs($this->admin)
        ->patchJson("/api/v1/admin/categories/{$category->id}", [
            'name' => ['en' => 'Premium notebooks', 'sk' => 'Prémiové zošity'],
        ])
        ->assertOk()
        ->assertJsonPath('data.name_translations.en', 'Premium notebooks')
        ->assertJsonPath('data.slug', 'notebooks');

    $category->refresh();

    expect($category->getTranslation('name', 'en'))->toBe('Premium notebooks')
        ->and($category->slug)->toBe('notebooks');
});

test('admins can partially update a product without replacing omitted fields', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'name' => ['en' => 'Notebook', 'sk' => 'Zošit'],
        'price' => 1200,
        'stock' => 10,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->patchJson("/api/v1/admin/products/{$product->id}", [
            'price' => 1500,
        ])
        ->assertOk()
        ->assertJsonPath('data.price', '15.00')
        ->assertJsonPath('data.stock', 10);

    $product->refresh();

    expect($product->price)->toBe(1500)
        ->and($product->stock)->toBe(10)
        ->and($product->category_id)->toBe($category->id)
        ->and($product->getTranslation('name', 'en'))->toBe('Notebook')
        ->and((bool) $product->is_active)->toBeTrue();
});

test('a supplied translated name must retain its English translation', function (string $endpoint, string $modelClass) {
    $model = $modelClass::factory()->create();

    $this->actingAs($this->admin)
        ->patchJson("/api/v1/admin/{$endpoint}/{$model->id}", [
            'name' => ['sk' => 'Nový názov'],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name', 'error.details');
})->with([
    'category' => ['categories', Category::class],
    'product' => ['products', Product::class],
]);

test('full replacement methods are not exposed for partial admin updates', function (string $endpoint, string $modelClass) {
    $model = $modelClass::factory()->create();

    $this->actingAs($this->admin)
        ->putJson("/api/v1/admin/{$endpoint}/{$model->id}", [])
        ->assertMethodNotAllowed()
        ->assertJsonPath('error.code', 'method_not_allowed');
})->with([
    'category' => ['categories', Category::class],
    'product' => ['products', Product::class],
]);
