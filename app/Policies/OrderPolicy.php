<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    // Admin can view all
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    // Customer can view their own, Admin can view all
    public function view(User $user, Order $order): bool
    {
        return $user->hasRole('admin') || $user->id === $order->user_id;
    }

    // Only Admin can update (status)
    public function update(User $user, Order $order): bool
    {
        return $user->hasRole('admin');
    }

    // Anyone with an account can create an order
    public function create(User $user): bool
    {
        return true;
    }
}
