<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Api\PaginationRequest;
use Illuminate\Validation\Rule;

class CatalogIndexRequest extends PaginationRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'scope' => ['sometimes', 'string', Rule::in(['active', 'trashed', 'all'])],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function queryParameters(): array
    {
        return [
            ...parent::queryParameters(),
            'scope' => [
                'description' => "Filter by 'active' (default), 'trashed', or 'all'.",
                'example' => 'all',
            ],
        ];
    }
}
