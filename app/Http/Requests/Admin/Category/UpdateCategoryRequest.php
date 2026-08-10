<?php

namespace App\Http\Requests\Admin\Category;

use App\Http\Requests\Traits\HasScribeBodyParameters;
use Illuminate\Foundation\Http\FormRequest;
use Knuckles\Scribe\Attributes\BodyParam;

#[BodyParam('name', 'object', 'Translatable name. When supplied, the English translation is required.', required: false, example: ['en' => 'Notebooks v2', 'sk' => 'Zošity v2'])]
class UpdateCategoryRequest extends FormRequest
{
    use HasScribeBodyParameters;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'array', 'required_array_keys:en'],
            'name.en' => ['string', 'max:255'],
            'name.sk' => ['nullable', 'string', 'max:255'],

            // disallow slug updates
            'slug' => ['prohibited'],
        ];
    }
}
