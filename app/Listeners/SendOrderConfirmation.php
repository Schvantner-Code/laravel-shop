<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\OrderUpdateMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmation implements ShouldQueue
{
    /**
     * This listener owns the queue boundary for order confirmations. Sending
     * here delivers the message inside this worker without creating a second
     * queued job for the same email.
     */
    public function handle(OrderPlaced $event): void
    {
        $event->order->load(['user', 'products']);

        Mail::to($event->order->user->email)->send(new OrderUpdateMail($event->order));
    }
}
