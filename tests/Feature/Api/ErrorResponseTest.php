<?php

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('validation failures use the API error envelope', function () {
    $this->postJson('/api/v1/auth/register')
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonPath('error.message', 'The request data is invalid.')
        ->assertJsonStructure(['error' => ['code', 'message', 'details' => ['name', 'email', 'password']]]);
});

test('invalid login credentials return an authentication error', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'incorrect-password',
    ])->assertUnauthorized()
        ->assertExactJson([
            'error' => [
                'code' => 'invalid_credentials',
                'message' => 'The provided credentials are invalid.',
            ],
        ]);
});

test('protected endpoints return an unauthenticated error', function () {
    $this->getJson('/api/v1/orders')
        ->assertUnauthorized()
        ->assertExactJson([
            'error' => [
                'code' => 'unauthenticated',
                'message' => 'Authentication is required.',
            ],
        ]);
});

test('policy denials return a forbidden error', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)
        ->getJson('/api/v1/admin/products')
        ->assertForbidden()
        ->assertJsonPath('error.code', 'forbidden');
});

test('missing models return a resource not found error', function () {
    $this->getJson('/api/v1/products/999999')
        ->assertNotFound()
        ->assertExactJson([
            'error' => [
                'code' => 'resource_not_found',
                'message' => 'The requested resource was not found.',
            ],
        ]);
});

test('unsupported methods return a method not allowed error', function () {
    $this->postJson('/api/v1/products')
        ->assertStatus(405)
        ->assertJsonPath('error.code', 'method_not_allowed');
});

test('non API errors retain the default web response', function () {
    $response = $this->get('/missing-web-route')->assertNotFound();

    expect($response->headers->get('content-type'))->toStartWith('text/html')
        ->and($response->getContent())->not->toContain('resource_not_found');
});

test('restoring an active resource returns a conflict error', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $category = Category::factory()->create();

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/categories/{$category->id}/restore")
        ->assertConflict()
        ->assertJsonPath('error.code', 'resource_not_deleted');
});

test('invalid order transitions return a conflict error', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $paymentMethod = PaymentMethod::create([
        'name' => ['en' => 'Cash on Delivery', 'sk' => 'Dobierka'],
        'slug' => 'cod',
        'is_active' => true,
    ]);
    $order = Order::create([
        'user_id' => $admin->id,
        'payment_method_id' => $paymentMethod->id,
        'status' => OrderStatus::Pending,
        'total_price' => 1000,
    ]);

    $this->actingAs($admin)
        ->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'paid'])
        ->assertConflict()
        ->assertJsonPath('error.code', 'invalid_order_transition');
});

test('production API errors do not expose exception details', function () {
    config()->set('app.debug', false);
    Route::get('/api/v1/test/server-error', fn () => throw new RuntimeException('Sensitive internal details'));

    $response = $this->getJson('/api/v1/test/server-error')
        ->assertInternalServerError()
        ->assertExactJson([
            'error' => [
                'code' => 'server_error',
                'message' => 'An unexpected error occurred.',
            ],
        ]);

    expect($response->getContent())->not->toContain('Sensitive internal details');
});
