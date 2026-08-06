<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProductIndexRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Knuckles\Scribe\Attributes\Header;

/**
 * @group Products & Categories (Public)
 */
#[Header('Accept-Language', example: 'en')]
class ProductController extends Controller
{
    /**
     * List all products
     *
     * Returns a paginated list of products. Supports filtering by category and searching by text.
     */
    public function index(ProductIndexRequest $request)
    {
        $query = Product::with('category')->where('is_active', true);

        // Filter by Category Slug
        $query->when($request->validated('category'), function (Builder $q, $slug) {
            $q->whereHas('category', function (Builder $catQuery) use ($slug) {
                $catQuery->where('slug', $slug);
            });
        });

        // Search by name or description
        $query->when($request->validated('search'), function (Builder $q, $search) {
            $term = "%{$search}%";
            $q->where(function (Builder $subQ) use ($term) {
                // use whereRaw to force the "Accent Insensitive" collation (ignore accents/diacritics)
                $subQ->whereRaw('name->"$.en" COLLATE utf8mb4_0900_ai_ci LIKE ?', [$term])
                    ->orWhereRaw('name->"$.sk" COLLATE utf8mb4_0900_ai_ci LIKE ?', [$term])
                    ->orWhereRaw('description->"$.en" COLLATE utf8mb4_0900_ai_ci LIKE ?', [$term])
                    ->orWhereRaw('description->"$.sk" COLLATE utf8mb4_0900_ai_ci LIKE ?', [$term]);
            });
        });

        return ProductResource::collection($query->paginate($request->perPage()));
    }

    /**
     * Get product details
     */
    public function show(string $id)
    {
        $product = Product::with('category')->findOrFail($id);

        return new ProductResource($product);
    }
}
