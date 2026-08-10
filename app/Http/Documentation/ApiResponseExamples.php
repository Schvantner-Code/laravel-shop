<?php

namespace App\Http\Documentation;

final class ApiResponseExamples
{
    public const TOKEN = [
        'data' => [
            'access_token' => '1|example-token',
            'token_type' => 'Bearer',
        ],
    ];

    public const ORDER_DATA = [
        'id' => 1,
        'status' => 'Pending',
        'status_code' => 'pending',
        'total_price' => '25.00',
        'created_at' => '2026-08-07T12:00:00+00:00',
        'items' => [
            [
                'product_id' => 1,
                'name' => 'Notebook',
                'quantity' => 2,
                'unit_price' => '12.50',
                'total' => '25.00',
            ],
        ],
    ];

    public const ORDER = ['data' => self::ORDER_DATA];

    public const ORDER_COLLECTION = [
        'data' => [self::ORDER_DATA],
        'links' => [
            'first' => 'http://localhost/api/v1/orders?page=1',
            'last' => 'http://localhost/api/v1/orders?page=1',
            'prev' => null,
            'next' => null,
        ],
        'meta' => [
            'current_page' => 1,
            'from' => 1,
            'last_page' => 1,
            'path' => 'http://localhost/api/v1/orders',
            'per_page' => 10,
            'to' => 1,
            'total' => 1,
        ],
    ];

    public const ADMIN_ORDER_COLLECTION = [
        'data' => [self::ORDER_DATA],
        'links' => [
            'first' => 'http://localhost/api/v1/admin/orders?page=1',
            'last' => 'http://localhost/api/v1/admin/orders?page=1',
            'prev' => null,
            'next' => null,
        ],
        'meta' => [
            'current_page' => 1,
            'from' => 1,
            'last_page' => 1,
            'path' => 'http://localhost/api/v1/admin/orders',
            'per_page' => 20,
            'to' => 1,
            'total' => 1,
        ],
    ];

    public const VALIDATION_FAILED = [
        'error' => [
            'code' => 'validation_failed',
            'message' => 'The request data is invalid.',
            'details' => [
                'field' => ['The field is required.'],
            ],
        ],
    ];

    public const INVALID_CREDENTIALS = [
        'error' => [
            'code' => 'invalid_credentials',
            'message' => 'The provided credentials are invalid.',
        ],
    ];

    public const UNAUTHENTICATED = [
        'error' => [
            'code' => 'unauthenticated',
            'message' => 'Authentication is required.',
        ],
    ];

    public const FORBIDDEN = [
        'error' => [
            'code' => 'forbidden',
            'message' => 'You are not authorized to perform this action.',
        ],
    ];

    public const NOT_FOUND = [
        'error' => [
            'code' => 'resource_not_found',
            'message' => 'The requested resource was not found.',
        ],
    ];

    public const CATEGORY_NOT_DELETED = [
        'error' => [
            'code' => 'resource_not_deleted',
            'message' => 'Category is not deleted.',
        ],
    ];

    public const PRODUCT_NOT_DELETED = [
        'error' => [
            'code' => 'resource_not_deleted',
            'message' => 'Product is not deleted.',
        ],
    ];

    public const INVALID_ORDER_TRANSITION = [
        'error' => [
            'code' => 'invalid_order_transition',
            'message' => 'Invalid transition from pending to paid for this payment method.',
        ],
    ];

    public const CHECKOUT_CONFLICT = [
        'error' => [
            'code' => 'insufficient_stock',
            'message' => 'One or more products do not have enough stock.',
            'details' => [
                'items' => [
                    [
                        'product_id' => 12,
                        'requested_quantity' => 3,
                        'available_stock' => 1,
                    ],
                ],
            ],
        ],
    ];
}
