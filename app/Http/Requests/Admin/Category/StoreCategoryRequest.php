<?php

namespace App\Http\Requests\Admin\Category;

use Illuminate\Foundation\Http\FormRequest;
use Knuckles\Scribe\Attributes\BodyParam;

#[BodyParam('name', 'object', 'Translatable name.', example: ['en' => 'Notebooks', 'sk' => 'Zošity'])]
#[BodyParam('slug', 'string', 'Unique URL identifier.', example: 'notebooks')]
class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Validate JSON translations
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.sk' => ['nullable', 'string', 'max:255'],

            // Unique slug check
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug'],
        ];
    }
}
