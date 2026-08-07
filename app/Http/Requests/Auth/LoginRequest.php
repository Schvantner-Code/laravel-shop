<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Traits\HasScribeBodyParameters;
use Illuminate\Foundation\Http\FormRequest;
use Knuckles\Scribe\Attributes\BodyParam;

#[BodyParam('email', 'string', example: 'admin@example.com')]
#[BodyParam('password', 'string', example: 'password')]
class LoginRequest extends FormRequest
{
    use HasScribeBodyParameters;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
