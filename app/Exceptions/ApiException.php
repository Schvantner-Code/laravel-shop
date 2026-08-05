<?php

namespace App\Exceptions;

use RuntimeException;

final class ApiException extends RuntimeException
{
    private function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status,
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
}
