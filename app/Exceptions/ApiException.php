<?php

namespace App\Exceptions;

use RuntimeException;

final class ApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $details
     */
    private function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    public static function invalidCredentials(): self
    {
        return new self('invalid_credentials', 'The provided credentials are invalid.', 401);
    }

    public static function resourceNotDeleted(string $resource): self
    {
        return new self('resource_not_deleted', "{$resource} is not deleted.", 409);
    }

    public static function invalidOrderTransition(string $from, string $to): self
    {
        return new self(
            'invalid_order_transition',
            "Invalid transition from {$from} to {$to} for this payment method.",
            409,
        );
    }

    public static function paymentMethodUnavailable(int $paymentMethodId): self
    {
        return new self(
            'payment_method_unavailable',
            'The selected payment method is no longer available.',
            409,
            ['payment_method_id' => $paymentMethodId],
        );
    }

    /**
     * @param  list<int>  $productIds
     */
    public static function productsUnavailable(array $productIds): self
    {
        return new self(
            'products_unavailable',
            'One or more selected products are no longer available.',
            409,
            ['product_ids' => $productIds],
        );
    }

    /**
     * Defensive fallback for callers that bypass StoreOrderRequest.
     *
     * Normal API requests reject duplicates through validation before the
     * action runs; this keeps the order invariant safe for future callers.
     *
     * @param  list<int>  $productIds
     */
    public static function duplicateProducts(array $productIds): self
    {
        return new self(
            'duplicate_products',
            'Each product may appear only once in an order.',
            422,
            ['product_ids' => $productIds],
        );
    }
}
