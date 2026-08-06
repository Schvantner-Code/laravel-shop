<?php

namespace App\Http\Requests\Api;

class ProductIndexRequest extends PaginationRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'category' => ['sometimes', 'string', 'max:255'],
            'search' => ['sometimes', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function queryParameters(): array
    {
        return [
            ...parent::queryParameters(),
            'category' => [
                'description' => 'Filter by category slug.',
                'example' => 'notebooks',
            ],
            'search' => [
                'description' => 'Search product names and descriptions.',
                'example' => 'pencil',
            ],
        ];
    }
}
