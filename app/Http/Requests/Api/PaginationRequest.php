<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PaginationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ];
    }

    public function perPage(int $default = 10): int
    {
        return $this->integer('per_page', $default);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function queryParameters(): array
    {
        return [
            'page' => [
                'description' => 'The page number.',
                'example' => 1,
            ],
            'per_page' => [
                'description' => 'Items per page (maximum 50).',
                'example' => 10,
            ],
        ];
    }
}
