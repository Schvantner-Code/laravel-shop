<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Documentation\ApiResponseExamples;
use App\Http\Requests\Api\ProductIndexRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Knuckles\Scribe\Attributes\Header;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

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
    #[ResponseFromApiResource(ProductResource::class, Product::class, collection: true, with: ['category'], paginate: 10, description: 'Products retrieved.')]
    #[ScribeResponse(ApiResponseExamples::VALIDATION_FAILED, 422, 'The query parameters are invalid.')]
    public function index(ProductIndexRequest $request): AnonymousResourceCollection
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
    #[ResponseFromApiResource(ProductResource::class, Product::class, with: ['category'], description: 'Product retrieved.')]
    #[ScribeResponse(ApiResponseExamples::NOT_FOUND, 404, 'The product was not found.')]
    public function show(string $id): ProductResource
    {
        $product = Product::with('category')->findOrFail($id);

        return new ProductResource($product);
    }
}
