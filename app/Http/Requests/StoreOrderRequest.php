<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Knuckles\Scribe\Attributes\BodyParam;

#[BodyParam('payment_method_id', 'integer', 'ID of the payment method (e.g. 1 for COD).', example: 1)]
#[BodyParam('items', 'array', 'Array of products to purchase.', example: [['product_id' => 1, 'quantity' => 2]])]
class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method_id' => ['required', 'exists:payment_methods,id'],

            'items' => ['required', 'array', 'min:1'],

            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('is_active', true),
            ],

            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
