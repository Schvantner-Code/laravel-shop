<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CategoryIndexRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Knuckles\Scribe\Attributes\Header;

/**
 * @group Products & Categories (Public)
 */
#[Header('Accept-Language', example: 'en')]
class CategoryController extends Controller
{
    /**
     * List all categories
     *
     * Returns a paginated list of categories. Supports searching by name.
     */
    public function index(CategoryIndexRequest $request): AnonymousResourceCollection
    {
        $query = Category::query();

        // Search by name
        $query->when($request->validated('search'), function (Builder $q, $search) {
            $term = "%{$search}%";
            $q->where(function (Builder $subQ) use ($term) {
                // use whereRaw to force the "Accent Insensitive" collation (ignore accents/diacritics)
                $subQ->whereRaw('name->"$.en" COLLATE utf8mb4_0900_ai_ci LIKE ?', [$term])
                    ->orWhereRaw('name->"$.sk" COLLATE utf8mb4_0900_ai_ci LIKE ?', [$term]);
            });
        });

        return CategoryResource::collection($query->paginate($request->perPage()));
    }
}
