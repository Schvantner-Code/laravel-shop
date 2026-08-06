<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Api\PaginationRequest;
use Illuminate\Validation\Rule;

class OrderIndexRequest extends PaginationRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'user_id' => ['sometimes', 'integer', 'min:1'],
            'product_id' => ['sometimes', 'integer', 'min:1'],
            'sort_by' => ['sometimes', 'string', Rule::in(['total_price', 'created_at'])],
            'sort_dir' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function queryParameters(): array
    {
        $parameters = parent::queryParameters();
        $parameters['per_page']['example'] = 20;

        return [
            ...$parameters,
            'user_id' => [
                'description' => 'Filter by customer ID.',
                'example' => 1,
            ],
            'product_id' => [
                'description' => 'Filter by a product contained in the order.',
                'example' => 1,
            ],
            'sort_by' => [
                'description' => "Sort by 'total_price' or 'created_at'.",
                'example' => 'created_at',
            ],
            'sort_dir' => [
                'description' => "Sort direction: 'asc' or 'desc'.",
                'example' => 'desc',
            ],
        ];
    }
}
