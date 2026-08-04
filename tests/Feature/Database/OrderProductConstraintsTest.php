<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

function createOrderForConstraintTest(): Order
{
    $paymentMethod = PaymentMethod::create([
        'name' => ['en' => 'Cash on Delivery', 'sk' => 'Dobierka'],
        'slug' => 'cod',
        'is_active' => true,
    ]);

    return Order::create([
        'user_id' => User::factory()->create()->id,
        'payment_method_id' => $paymentMethod->id,
        'status' => OrderStatus::Pending,
        'total_price' => 1000,
    ]);
}

test('an order cannot contain the same product more than once', function () {
    $order = createOrderForConstraintTest();
    $product = Product::factory()->create();
    $pivotData = ['quantity' => 1, 'unit_price' => $product->price];

    $order->products()->attach($product->id, $pivotData);

    expect(fn () => $order->products()->attach($product->id, $pivotData))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('deleting an order cascades to its product rows', function () {
    $order = createOrderForConstraintTest();
    $product = Product::factory()->create();

    $order->products()->attach($product->id, [
        'quantity' => 1,
        'unit_price' => $product->price,
    ]);

    $order->delete();

    $this->assertDatabaseMissing('order_product', [
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);
    $this->assertDatabaseHas('products', ['id' => $product->id]);
});
