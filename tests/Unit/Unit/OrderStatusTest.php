<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethodSlug;
use App\Models\Order;
use App\Models\PaymentMethod;

test('standard payment allows pending to paid transition', function () {
    // 1. Mock an order with Bank Transfer in memory
    $order = new Order;
    $paymentMethod = new PaymentMethod(['slug' => PaymentMethodSlug::BankTransfer->value]);
    $order->setRelation('paymentMethod', $paymentMethod);

    // 2. Assert logic
    $status = OrderStatus::Pending;

    // Should allow Paid
    expect($status->canTransitionTo(OrderStatus::Paid, $order))->toBeTrue();

    // Should NOT allow Shipped directly (must pay first)
    expect($status->canTransitionTo(OrderStatus::Shipped, $order))->toBeFalse();
});

test('cod payment denies pending to paid transition', function () {
    // 1. Mock an order with COD in memory
    $order = new Order;
    $paymentMethod = new PaymentMethod(['slug' => PaymentMethodSlug::COD->value]);
    $order->setRelation('paymentMethod', $paymentMethod);

    // 2. Assert logic
    $status = OrderStatus::Pending;

    // Should NOT allow Paid directly (must ship first for COD)
    expect($status->canTransitionTo(OrderStatus::Paid, $order))->toBeFalse();

    // Should allow Shipped
    expect($status->canTransitionTo(OrderStatus::Shipped, $order))->toBeTrue();
});

test('final states are immutable', function () {
    $status = OrderStatus::Completed;
    expect($status->canTransitionTo(OrderStatus::Pending))->toBeFalse();

    $status = OrderStatus::Cancelled;
    expect($status->canTransitionTo(OrderStatus::Paid))->toBeFalse();
});
