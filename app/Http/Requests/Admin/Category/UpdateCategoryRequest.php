<?php

namespace App\Http\Requests\Admin\Category;

use Illuminate\Foundation\Http\FormRequest;
use Knuckles\Scribe\Attributes\BodyParam;

#[BodyParam('name', 'object', 'Translatable name.', example: ['en' => 'Notebooks v2', 'sk' => 'Zošity v2'])]
class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.sk' => ['nullable', 'string', 'max:255'],

            // disallow slug updates
            'slug' => ['prohibited'],
        ];
    }
}
