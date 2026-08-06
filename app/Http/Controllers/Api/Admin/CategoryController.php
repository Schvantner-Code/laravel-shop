<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CatalogIndexRequest;
use App\Http\Requests\Admin\Category\StoreCategoryRequest;
use App\Http\Requests\Admin\Category\UpdateCategoryRequest;
use App\Http\Resources\Admin\AdminCategoryResource;
use App\Models\Category;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Group;

#[Group('Admin Management')]
#[Authenticated]
class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function index(CatalogIndexRequest $request)
    {
        $this->authorize('viewAny', Category::class);

        $query = Category::query();

        $scope = $request->validated('scope', 'active');

        match ($scope) {
            'trashed' => $query->onlyTrashed(),
            'all' => $query->withTrashed(),
            default => $query,
        };

        return AdminCategoryResource::collection($query->paginate($request->perPage()));
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
            throw ApiException::resourceNotDeleted('Category');
        }

        $this->authorize('restore', $category);

        $category->restore();

        return new AdminCategoryResource($category);
    }
}
