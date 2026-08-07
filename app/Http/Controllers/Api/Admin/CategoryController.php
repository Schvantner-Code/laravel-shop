<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Documentation\ApiResponseExamples;
use App\Http\Requests\Admin\CatalogIndexRequest;
use App\Http\Requests\Admin\Category\StoreCategoryRequest;
use App\Http\Requests\Admin\Category\UpdateCategoryRequest;
use App\Http\Resources\Admin\AdminCategoryResource;
use App\Models\Category;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('Admin Management')]
#[Authenticated]
#[ScribeResponse(ApiResponseExamples::UNAUTHENTICATED, 401, 'A valid access token is required.')]
#[ScribeResponse(ApiResponseExamples::FORBIDDEN, 403, 'Administrator access is required.')]
class CategoryController extends Controller
{
    use AuthorizesRequests;

    #[ResponseFromApiResource(AdminCategoryResource::class, Category::class, collection: true, paginate: 10, description: 'Categories retrieved for administration.')]
    #[ScribeResponse(ApiResponseExamples::VALIDATION_FAILED, 422, 'The query parameters are invalid.')]
    public function index(CatalogIndexRequest $request): AnonymousResourceCollection
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

    #[ResponseFromApiResource(AdminCategoryResource::class, Category::class, status: 201, description: 'Category created.')]
    #[ScribeResponse(ApiResponseExamples::VALIDATION_FAILED, 422, 'The category data is invalid.')]
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $category = Category::create($request->validated());

        return (new AdminCategoryResource($category))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[ResponseFromApiResource(AdminCategoryResource::class, Category::class, description: 'Category updated.')]
    #[ScribeResponse(ApiResponseExamples::NOT_FOUND, 404, 'The category was not found.')]
    #[ScribeResponse(ApiResponseExamples::VALIDATION_FAILED, 422, 'The category data is invalid.')]
    public function update(UpdateCategoryRequest $request, Category $category): AdminCategoryResource
    {
        $this->authorize('update', $category);

        $category->update($request->validated());

        return new AdminCategoryResource($category);
    }

    #[ScribeResponse(status: 204, description: 'Category deleted.')]
    #[ScribeResponse(ApiResponseExamples::NOT_FOUND, 404, 'The category was not found.')]
    public function destroy(Category $category): Response
    {
        $this->authorize('delete', $category);

        $category->delete();

        return response()->noContent();
    }

    /**
     * Restore a deleted category
     */
    #[ResponseFromApiResource(AdminCategoryResource::class, Category::class, description: 'Category restored.')]
    #[ScribeResponse(ApiResponseExamples::NOT_FOUND, 404, 'The category was not found.')]
    #[ScribeResponse(ApiResponseExamples::CATEGORY_NOT_DELETED, 409, 'The category is already active.')]
    public function restore(int $id): AdminCategoryResource
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
