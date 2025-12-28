<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->label(),
            'status_code' => $this->status->value,
            'total_price' => number_format($this->total_price / 100, 2, '.', ''),
            'created_at' => $this->created_at->toIso8601String(),

            'items' => $this->products->map(function ($product) {
                return [
                    'product_id' => $product->id,
                    'name' => $product->getTranslation('name', app()->getLocale()),
                    'quantity' => $product->pivot->quantity,
                    'unit_price' => number_format($product->pivot->unit_price / 100, 2, '.', ''),
                    'total' => number_format(($product->pivot->unit_price * $product->pivot->quantity) / 100, 2, '.', ''),
                ];
            }),
        ];
    }
}
