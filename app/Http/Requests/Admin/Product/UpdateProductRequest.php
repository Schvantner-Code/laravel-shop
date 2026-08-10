<?php

namespace App\Http\Requests\Admin\Product;

use App\Http\Requests\Traits\HasScribeBodyParameters;
use Illuminate\Foundation\Http\FormRequest;
use Knuckles\Scribe\Attributes\BodyParam;

#[BodyParam('category_id', 'integer', required: false, example: 1)]
#[BodyParam('name', 'object', 'When supplied, the English translation is required.', required: false, example: ['en' => 'Pro Pen', 'sk' => 'Pro Pero'])]
#[BodyParam('price', 'integer', 'Price in cents.', required: false, example: 1250)]
#[BodyParam('stock', 'integer', 'Available inventory units.', required: false, example: 25)]
class UpdateProductRequest extends FormRequest
{
    use HasScribeBodyParameters;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'exists:categories,id'],

            'name' => ['sometimes', 'array', 'required_array_keys:en'],
            'name.en' => ['string', 'max:255'],
            'name.sk' => ['nullable', 'string', 'max:255'],

            'description' => ['sometimes', 'nullable', 'array'],
            'description.en' => ['nullable', 'string'],
            'description.sk' => ['nullable', 'string'],

            'price' => ['sometimes', 'integer', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
