<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\OrderUpdateMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmation implements ShouldQueue
{
    public function handle(OrderPlaced $event): void
    {
        $event->order->load(['user', 'products']);

        Mail::to($event->order->user->email)->send(new OrderUpdateMail($event->order));
    }
}
