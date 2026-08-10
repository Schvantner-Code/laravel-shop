<?php

use App\Actions\CreateOrder;
use App\Exceptions\ApiException;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
    $this->paymentMethod = PaymentMethod::create([
        'name' => ['en' => 'Cash on Delivery', 'sk' => 'Dobierka'],
        'slug' => 'cod',
        'is_active' => true,
    ]);
});

test('checkout rejects inactive payment methods', function () {
    $product = Product::factory()->create();
    $this->paymentMethod->update(['is_active' => false]);

    $this->actingAs($this->customer)
        ->postJson('/api/v1/orders', checkoutData($this->paymentMethod, [
            ['product_id' => $product->id, 'quantity' => 1],
        ]))
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonValidationErrors('payment_method_id', 'error.details');

    $this->assertDatabaseCount('orders', 0);
});

test('checkout rejects missing inactive and deleted products', function () {
    $inactiveProduct = Product::factory()->create(['is_active' => false]);
    $deletedProduct = Product::factory()->create();
    $deletedProduct->delete();

    foreach ([999999, $inactiveProduct->id, $deletedProduct->id] as $productId) {
        $this->actingAs($this->customer)
            ->postJson('/api/v1/orders', checkoutData($this->paymentMethod, [
                ['product_id' => $productId, 'quantity' => 1],
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonValidationErrors('items.0.product_id', 'error.details');
    }

    $this->assertDatabaseCount('orders', 0);
});

test('checkout rejects duplicate products', function () {
    $product = Product::factory()->create();

    $this->actingAs($this->customer)
        ->postJson('/api/v1/orders', checkoutData($this->paymentMethod, [
            ['product_id' => $product->id, 'quantity' => 1],
            ['product_id' => $product->id, 'quantity' => 2],
        ]))
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonValidationErrors('items.1.product_id', 'error.details');

    $this->assertDatabaseCount('orders', 0);
});

test('checkout calculates and snapshots prices on the server', function () {
    $firstProduct = Product::factory()->create(['price' => 1250, 'stock' => 2]);
    $secondProduct = Product::factory()->create(['price' => 300, 'stock' => 3]);

    $response = $this->actingAs($this->customer)
        ->postJson('/api/v1/orders', checkoutData($this->paymentMethod, [
            ['product_id' => $firstProduct->id, 'quantity' => 2],
            ['product_id' => $secondProduct->id, 'quantity' => 3],
        ]))
        ->assertCreated()
        ->assertJsonPath('data.total_price', '34.00');

    $orderId = $response->json('data.id');

    $this->assertDatabaseHas('orders', [
        'id' => $orderId,
        'total_price' => 3400,
    ]);
    $this->assertDatabaseHas('order_product', [
        'order_id' => $orderId,
        'product_id' => $firstProduct->id,
        'quantity' => 2,
        'unit_price' => 1250,
    ]);
    $this->assertDatabaseHas('products', ['id' => $firstProduct->id, 'stock' => 0]);
    $this->assertDatabaseHas('products', ['id' => $secondProduct->id, 'stock' => 0]);
});

test('checkout reports all insufficient items without changing stock', function () {
    Event::fake([OrderPlaced::class]);

    $firstProduct = Product::factory()->create(['stock' => 1]);
    $secondProduct = Product::factory()->create(['stock' => 0]);

    $this->actingAs($this->customer)
        ->postJson('/api/v1/orders', checkoutData($this->paymentMethod, [
            ['product_id' => $firstProduct->id, 'quantity' => 2],
            ['product_id' => $secondProduct->id, 'quantity' => 3],
        ]))
        ->assertConflict()
        ->assertExactJson([
            'error' => [
                'code' => 'insufficient_stock',
                'message' => 'One or more products do not have enough stock.',
                'details' => [
                    'items' => [
                        [
                            'product_id' => $firstProduct->id,
                            'requested_quantity' => 2,
                            'available_stock' => 1,
                        ],
                        [
                            'product_id' => $secondProduct->id,
                            'requested_quantity' => 3,
                            'available_stock' => 0,
                        ],
                    ],
                ],
            ],
        ]);

    $this->assertDatabaseHas('products', ['id' => $firstProduct->id, 'stock' => 1]);
    $this->assertDatabaseHas('products', ['id' => $secondProduct->id, 'stock' => 0]);
    $this->assertDatabaseCount('orders', 0);
    $this->assertDatabaseCount('order_product', 0);
    Event::assertNotDispatched(OrderPlaced::class);
});

test('stock decrements roll back when order creation fails', function () {
    Event::fake([OrderPlaced::class]);

    $product = Product::factory()->create(['stock' => 5]);
    $data = checkoutData($this->paymentMethod, [
        ['product_id' => $product->id, 'quantity' => 2],
    ]);

    // Removing the authenticated user simulates a database failure after the
    // stock decrement but before the order insert can satisfy its foreign key.
    $this->customer->delete();

    expect(fn () => app(CreateOrder::class)->execute($this->customer, $data))
        ->toThrow(QueryException::class);

    $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
    $this->assertDatabaseCount('orders', 0);
    Event::assertNotDispatched(OrderPlaced::class);
});

test('order creation rechecks payment and product availability inside its transaction', function () {
    $product = Product::factory()->create();
    $this->paymentMethod->update(['is_active' => false]);

    $createOrder = app(CreateOrder::class);
    $data = checkoutData($this->paymentMethod, [
        ['product_id' => $product->id, 'quantity' => 1],
    ]);

    expect(fn () => $createOrder->execute($this->customer, $data))
        ->toThrow(function (ApiException $exception): void {
            expect($exception->errorCode)->toBe('payment_method_unavailable')
                ->and($exception->status)->toBe(409)
                ->and($exception->details)->toBe([
                    'payment_method_id' => $this->paymentMethod->id,
                ]);
        });

    $this->paymentMethod->update(['is_active' => true]);
    $product->update(['is_active' => false]);

    expect(fn () => $createOrder->execute($this->customer, $data))
        ->toThrow(function (ApiException $exception) use ($product): void {
            expect($exception->errorCode)->toBe('products_unavailable')
                ->and($exception->status)->toBe(409)
                ->and($exception->details)->toBe([
                    'product_ids' => [$product->id],
                ]);
        });

    $this->assertDatabaseCount('orders', 0);
});

test('checkout reports products that become unavailable after validation', function () {
    $product = Product::factory()->create();

    $this->mock(CreateOrder::class, function (MockInterface $mock) use ($product): void {
        $mock->shouldReceive('execute')
            ->once()
            ->andThrow(ApiException::productsUnavailable([$product->id]));
    });

    $this->actingAs($this->customer)
        ->postJson('/api/v1/orders', checkoutData($this->paymentMethod, [
            ['product_id' => $product->id, 'quantity' => 1],
        ]))
        ->assertConflict()
        ->assertExactJson([
            'error' => [
                'code' => 'products_unavailable',
                'message' => 'One or more selected products are no longer available.',
                'details' => [
                    'product_ids' => [$product->id],
                ],
            ],
        ]);

    $this->assertDatabaseCount('orders', 0);
});

/**
 * @param  array<int, array{product_id: int, quantity: int}>  $items
 * @return array{payment_method_id: int, items: array<int, array{product_id: int, quantity: int}>}
 */
function checkoutData(PaymentMethod $paymentMethod, array $items): array
{
    return [
        'payment_method_id' => $paymentMethod->id,
        'items' => $items,
    ];
}
