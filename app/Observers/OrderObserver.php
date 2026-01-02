<?php

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethodSlug;
use App\Mail\OrderUpdateMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Only send email if the 'status' column changed
        if ($order->isDirty('status')) {
            $order->load(['user', 'paymentMethod']);

            // Skip if COD and status became PAID (no email needed)
            $isCod = $order->paymentMethod->slug === PaymentMethodSlug::COD->value;
            $isPaid = $order->status === OrderStatus::Paid;
            if ($isCod && $isPaid) {
                return;
            }

            Mail::to($order->user->email)->send(new OrderUpdateMail($order));
        }
    }
}
