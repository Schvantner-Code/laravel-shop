<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Knuckles\Scribe\Attributes\BodyParam;

#[BodyParam('name', 'string', 'The full name of the user.', example: 'John Smith')]
#[BodyParam('email', 'string', 'A unique email address.', example: 'johnsmith@example.com')]
#[BodyParam('password', 'string', 'Must be at least 8 characters long ', example: 'secret123')]
#[BodyParam('password_confirmation', 'string', 'Must match the password.', example: 'secret123')]
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'min:8', 'confirmed', Password::defaults()],
        ];
    }
}
