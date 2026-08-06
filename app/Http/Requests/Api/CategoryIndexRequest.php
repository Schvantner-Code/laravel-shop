<?php

namespace App\Http\Requests\Api;

class CategoryIndexRequest extends PaginationRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
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
            'search' => [
                'description' => 'Search category names.',
                'example' => 'notebooks',
            ],
        ];
    }
}
