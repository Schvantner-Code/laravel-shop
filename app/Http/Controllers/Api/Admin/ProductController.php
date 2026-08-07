<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Documentation\ApiResponseExamples;
use App\Http\Requests\Admin\CatalogIndexRequest;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Http\Resources\Admin\AdminProductResource;
use App\Models\Product;
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
class ProductController extends Controller
{
    use AuthorizesRequests;

    /**
     * List Products (Admin)
     *
     * View all products, including inactive or deleted ones.
     */
    #[ResponseFromApiResource(AdminProductResource::class, Product::class, collection: true, with: ['category'], paginate: 10, description: 'Products retrieved for administration.')]
    #[ScribeResponse(ApiResponseExamples::VALIDATION_FAILED, 422, 'The query parameters are invalid.')]
    public function index(CatalogIndexRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::with('category');

        // Filter Logic
        $scope = $request->validated('scope', 'active');

        match ($scope) {
            'trashed' => $query->onlyTrashed(), // Show ONLY deleted
            'all' => $query->withTrashed(), // Show Active + Deleted
            default => $query, // Show Active only (default)
        };

        return AdminProductResource::collection($query->paginate($request->perPage()));
    }

    #[ResponseFromApiResource(AdminProductResource::class, Product::class, status: 201, with: ['category'], description: 'Product created.')]
    #[ScribeResponse(ApiResponseExamples::VALIDATION_FAILED, 422, 'The product data is invalid.')]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = Product::create($request->validated());

        return (new AdminProductResource($product))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[ResponseFromApiResource(AdminProductResource::class, Product::class, with: ['category'], description: 'Product updated.')]
    #[ScribeResponse(ApiResponseExamples::NOT_FOUND, 404, 'The product was not found.')]
    #[ScribeResponse(ApiResponseExamples::VALIDATION_FAILED, 422, 'The product data is invalid.')]
    public function update(UpdateProductRequest $request, Product $product): AdminProductResource
    {
        $this->authorize('update', $product);

        $product->update($request->validated());

        return new AdminProductResource($product);
    }

    #[ScribeResponse(status: 204, description: 'Product deleted.')]
    #[ScribeResponse(ApiResponseExamples::NOT_FOUND, 404, 'The product was not found.')]
    public function destroy(Product $product): Response
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->noContent();
    }

    /**
     * Restore a deleted product
     */
    #[ResponseFromApiResource(AdminProductResource::class, Product::class, with: ['category'], description: 'Product restored.')]
    #[ScribeResponse(ApiResponseExamples::NOT_FOUND, 404, 'The product was not found.')]
    #[ScribeResponse(ApiResponseExamples::PRODUCT_NOT_DELETED, 409, 'The product is already active.')]
    public function restore(int $id): AdminProductResource
    {
        $product = Product::withTrashed()->findOrFail($id);

        if ($product->deleted_at === null) {
            throw ApiException::resourceNotDeleted('Product');
        }

        $this->authorize('restore', $product);

        $product->restore();

        return new AdminProductResource($product);
    }
}
