<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Header;
use Knuckles\Scribe\Attributes\QueryParam;

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
    #[QueryParam('category', 'string', "Filter by category slug (e.g. 'notebooks').", example: 'notebooks', required: false)]
    #[QueryParam('search', 'string', 'Search name or description.', example: 'pencil', required: false)]
    #[QueryParam('per_page', 'integer', 'Items per page (Max 50).', example: 10)]
    #[QueryParam('page', 'integer', 'The page number.', example: 1)]
    public function index(Request $request)
    {
        $query = Product::with('category')->where('is_active', true);

        // Filter by Category Slug
        $query->when($request->query('category'), function (Builder $q, $slug) {
            $q->whereHas('category', function (Builder $catQuery) use ($slug) {
                $catQuery->where('slug', $slug);
            });
        });

        // Search by name or description
        $query->when($request->query('search'), function (Builder $q, $search) {
            $term = "%{$search}%";
            $q->where(function (Builder $subQ) use ($term) {
                // use whereRaw to force the "Accent Insensitive" collation (ignore accents/diacritics)
                $subQ->whereRaw('name->"$.en" COLLATE utf8mb4_0900_ai_ci LIKE ?', [$term])
                    ->orWhereRaw('name->"$.sk" COLLATE utf8mb4_0900_ai_ci LIKE ?', [$term])
                    ->orWhereRaw('description->"$.en" COLLATE utf8mb4_0900_ai_ci LIKE ?', [$term])
                    ->orWhereRaw('description->"$.sk" COLLATE utf8mb4_0900_ai_ci LIKE ?', [$term]);
            });
        });

        // allow per_page up to 50
        $perPage = $request->input('per_page', 10);
        if ($perPage > 50 || $perPage < 1) {
            $perPage = 50;
        }

        // Pagination
        return ProductResource::collection($query->paginate($perPage));
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
