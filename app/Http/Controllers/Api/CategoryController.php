<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Header;
use Knuckles\Scribe\Attributes\QueryParam;

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
    #[QueryParam('search', 'string', 'Search category name.', example: 'notebooks', required: false)]
    #[QueryParam('per_page', 'integer', 'Items per page (Max 50).', example: 10)]
    #[QueryParam('page', 'integer', 'The page number.', example: 1)]
    public function index(Request $request)
    {
        $query = Category::query();

        // Search by name
        $query->when($request->query('search'), function (Builder $q, $search) {
            $term = "%{$search}%";
            $q->where(function (Builder $subQ) use ($term) {
                // use whereRaw to force the "Accent Insensitive" collation (ignore accents/diacritics)
                $subQ->whereRaw('name->"$.en" COLLATE utf8mb4_0900_ai_ci LIKE ?', [$term])
                    ->orWhereRaw('name->"$.sk" COLLATE utf8mb4_0900_ai_ci LIKE ?', [$term]);
            });
        });

        // allow per_page up to 50
        $perPage = $request->input('per_page', 10);
        if ($perPage > 50 || $perPage < 1) {
            $perPage = 50;
        }

        return CategoryResource::collection($query->paginate($perPage));
    }
}
