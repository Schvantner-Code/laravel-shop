<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Category\StoreCategoryRequest;
use App\Http\Requests\Admin\Category\UpdateCategoryRequest;
use App\Http\Resources\Admin\AdminCategoryResource;
use App\Models\Category;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;

#[Group('Admin Management')]
#[Authenticated]
class CategoryController extends Controller
{
    use AuthorizesRequests;

    #[QueryParam('scope', 'string', "Filter list: 'active' (default), 'trashed', or 'all'.", example: 'all')]
    #[QueryParam('per_page', 'integer', 'Items per page (Max 50).', example: 10)]
    #[QueryParam('page', 'integer', 'The page number.', example: 1)]
    public function index(Request $request)
    {
        $this->authorize('viewAny', Category::class);

        $query = Category::query();

        $scope = $request->query('scope', 'active');

        match ($scope) {
            'trashed' => $query->onlyTrashed(),
            'all' => $query->withTrashed(),
            default => $query,
        };

        // allow per_page up to 50
        $perPage = $request->input('per_page', 10);
        if ($perPage > 50 || $perPage < 1) {
            $perPage = 50;
        }

        return AdminCategoryResource::collection($query->paginate($perPage));
    }

    public function store(StoreCategoryRequest $request)
    {
        $this->authorize('create', Category::class);

        $category = Category::create($request->validated());

        return new AdminCategoryResource($category);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $this->authorize('update', $category);

        $category->update($request->validated());

        return new AdminCategoryResource($category);
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        $category->delete();

        return response()->noContent();
    }

    /**
     * Restore a deleted category
     */
    public function restore(int $id)
    {
        $category = Category::withTrashed()->findOrFail($id);

        if ($category->deleted_at === null) {
            return response()->json(['message' => 'Category is not deleted.'], 400);
        }

        $this->authorize('restore', $category);

        $category->restore();

        return new AdminCategoryResource($category);
    }
}
