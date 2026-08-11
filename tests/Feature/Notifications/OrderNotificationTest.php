<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethodSlug;
use App\Events\OrderPlaced;
use App\Listeners\SendOrderConfirmation;
use App\Mail\OrderUpdateMail;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Observers\OrderObserver;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->user = User::factory()->create();
});

test('order status changes queue customer email after commit', function () {
    $order = notificationOrder($this->user, PaymentMethodSlug::BankTransfer);

    DB::transaction(
        fn () => $order->update(['status' => OrderStatus::Paid])
    );

    Mail::assertQueued(OrderUpdateMail::class, function (OrderUpdateMail $mail) use ($order) {
        return $mail->order->is($order)
            && $mail->order->status === OrderStatus::Paid
            && $mail->hasTo($this->user->email);
    });
    Mail::assertNothingSent();
});

test('rolled back order status changes do not queue customer email', function () {
    $order = notificationOrder($this->user, PaymentMethodSlug::BankTransfer);

    expect(fn () => DB::transaction(function () use ($order) {
        $order->update(['status' => OrderStatus::Paid]);

        throw new RuntimeException('Force rollback after status update.');
    }))->toThrow(RuntimeException::class);

    Mail::assertNothingOutgoing();
});

test('unrelated order updates do not send customer email', function () {
    $order = notificationOrder($this->user, PaymentMethodSlug::BankTransfer);

    $order->update(['total_price' => 2500]);

    Mail::assertNothingOutgoing();
});

test('cod paid transition does not send a redundant customer email', function () {
    $order = notificationOrder($this->user, PaymentMethodSlug::COD, OrderStatus::Shipped);

    $order->update(['status' => OrderStatus::Paid]);

    Mail::assertNothingOutgoing();
});

test('queued confirmation listener sends without queueing a second mail job', function () {
    $order = notificationOrder($this->user, PaymentMethodSlug::BankTransfer);
    $listener = new SendOrderConfirmation;

    $listener->handle(new OrderPlaced($order));

    expect($listener)->toBeInstanceOf(ShouldQueue::class)
        ->and(new OrderObserver)->toBeInstanceOf(ShouldHandleEventsAfterCommit::class)
        ->and(new OrderUpdateMail($order))->not->toBeInstanceOf(ShouldQueue::class);

    Mail::assertSent(OrderUpdateMail::class, $this->user->email);
    Mail::assertNothingQueued();
});

function notificationOrder(
    User $user,
    PaymentMethodSlug $paymentMethodSlug,
    OrderStatus $status = OrderStatus::Pending,
): Order {
    $paymentMethod = PaymentMethod::create([
        'name' => ['en' => 'Test payment', 'sk' => 'Testovacia platba'],
        'slug' => $paymentMethodSlug->value,
        'is_active' => true,
    ]);

    return Order::create([
        'user_id' => $user->id,
        'payment_method_id' => $paymentMethod->id,
        'status' => $status,
        'total_price' => 1000,
    ]);
}
