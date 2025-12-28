<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', app()->getLocale()),
            'description' => $this->getTranslation('description', app()->getLocale()),
            // Convert cents to standard currency format (1000 -> 10.00)
            'price' => number_format($this->price / 100, 2, '.', ''),
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
