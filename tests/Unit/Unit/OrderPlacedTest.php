<?php

use App\Events\OrderPlaced;
use App\Models\Order;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

test('order placed events wait for a successful database commit', function () {
    expect(new OrderPlaced(new Order))
        ->toBeInstanceOf(ShouldDispatchAfterCommit::class);
});
