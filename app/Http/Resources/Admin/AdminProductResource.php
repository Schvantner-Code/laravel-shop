<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;

class AdminProductResource extends ProductResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        // Merge Admin-only fields
        return array_merge($data, [
            'name_translations' => $this->getTranslations('name'),
            'description_translations' => $this->getTranslations('description'),

            'is_active' => $this->is_active,
            'is_deleted' => $this->trashed(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ]);
    }
}
