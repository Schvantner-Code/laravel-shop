<?php

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethodSlug;
use App\Mail\OrderUpdateMail;
use App\Models\Order;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Mail;

/**
 * Defer notification decisions until the surrounding database transaction has
 * committed, so rolled-back status changes cannot publish email jobs.
 */
class OrderObserver implements ShouldHandleEventsAfterCommit
{
    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        $order->load(['user', 'paymentMethod']);

        // COD payment is collected on delivery, so the later paid transition
        // is an accounting update rather than a customer-facing milestone.
        $isCod = $order->paymentMethod->slug === PaymentMethodSlug::COD->value;
        $isPaid = $order->status === OrderStatus::Paid;

        if ($isCod && $isPaid) {
            return;
        }

        Mail::to($order->user->email)->queue(new OrderUpdateMail($order));
    }
}
