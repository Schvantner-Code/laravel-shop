<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;
use Knuckles\Scribe\Attributes\BodyParam;

#[BodyParam('category_id', 'integer', example: 1)]
#[BodyParam('name', 'object', example: ['en' => 'Pro Pen', 'sk' => 'Pro Pero'])]
#[BodyParam('price', 'integer', 'Price in cents.', example: 1250)]
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],

            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.sk' => ['nullable', 'string', 'max:255'],

            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string'],
            'description.sk' => ['nullable', 'string'],

            'price' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
