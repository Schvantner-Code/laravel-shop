<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;

class AdminCategoryResource extends CategoryResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        return array_merge($data, [
            'name_translations' => $this->getTranslations('name'),
            'is_deleted' => $this->trashed(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ]);
    }
}
