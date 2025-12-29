<?php

namespace App\Enums;

use App\Models\Order;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Paid => __('Paid'),
            self::Shipped => __('Shipped'),
            self::Completed => __('Completed'),
            self::Cancelled => __('Cancelled'),
        };
    }

    /**
     * The State Machine Logic
     * Returns true if we are allowed to switch to the new status.
     */
    public function canTransitionTo(self $newStatus, ?Order $order = null): bool
    {
        // If the order is cancelled or completed, we can't change anything.
        if ($this === self::Cancelled || $this === self::Completed) {
            return false;
        }

        // An order can always be cancelled
        if ($newStatus === self::Cancelled) {
            return true;
        }

        $slug = $order?->paymentMethod?->slug;

        // Different flow for COD vs standard payments
        if ($slug === PaymentMethodSlug::COD->value) {
            return $this->handleCodTransition($newStatus);
        }

        return $this->handleStandardTransition($newStatus);
    }

    private function handleStandardTransition(self $newStatus): bool
    {
        // Standard (Bank/Card): Pending -> Paid -> Shipped -> Completed
        return match ($this) {
            self::Pending => $newStatus === self::Paid,
            self::Paid => $newStatus === self::Shipped,
            self::Shipped => $newStatus === self::Completed,
            default => false,
        };
    }

    private function handleCodTransition(self $newStatus): bool
    {
        // COD: Pending -> Shipped -> Paid -> Completed
        return match ($this) {
            self::Pending => $newStatus === self::Shipped,
            self::Shipped => $newStatus === self::Paid,
            self::Paid => $newStatus === self::Completed,
            default => false,
        };
    }
}
