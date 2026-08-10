<?php

use App\Events\OrderPlaced;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('registration returns a created token resource', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'New Customer',
        'email' => 'customer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertCreated()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonStructure(['data' => ['access_token', 'token_type']])
        ->assertJsonMissingPath('access_token');
});

test('login returns a token resource', function () {
    $user = User::factory()->create(['password' => 'password']);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonStructure(['data' => ['access_token', 'token_type']])
        ->assertJsonMissingPath('access_token');
});

test('logout revokes the current token and returns no content', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token');

    $this->withToken($token->plainTextToken)
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->id,
    ]);
});

test('admin catalog creation returns created resources', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $categoryResponse = $this->actingAs($admin)->postJson('/api/v1/admin/categories', [
        'name' => ['en' => 'Notebooks', 'sk' => 'Zošity'],
        'slug' => 'notebooks',
    ])->assertCreated()
        ->assertJsonPath('data.slug', 'notebooks');

    $categoryId = $categoryResponse->json('data.id');

    $this->actingAs($admin)->postJson('/api/v1/admin/products', [
        'category_id' => $categoryId,
        'name' => ['en' => 'Notebook', 'sk' => 'Zošit'],
        'price' => 1000,
        'stock' => 20,
        'is_active' => true,
    ])->assertCreated()
        ->assertJsonPath('data.price', '10.00');
});

test('checkout returns a created order resource', function () {
    Event::fake([OrderPlaced::class]);

    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $paymentMethod = PaymentMethod::create([
        'name' => ['en' => 'Cash on Delivery', 'sk' => 'Dobierka'],
        'slug' => 'cod',
        'is_active' => true,
    ]);
    $product = Product::factory()->create(['price' => 1250, 'stock' => 2]);

    $this->actingAs($customer)->postJson('/api/v1/orders', [
        'payment_method_id' => $paymentMethod->id,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
    ])->assertCreated()
        ->assertJsonPath('data.total_price', '25.00')
        ->assertJsonPath('data.status_code', 'pending')
        ->assertJsonCount(1, 'data.items');
});
