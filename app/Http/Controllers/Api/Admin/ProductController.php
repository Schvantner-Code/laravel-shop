<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
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

#[Group('Admin Management')]
#[Authenticated]
class ProductController extends Controller
{
    use AuthorizesRequests;

    /**
     * List Products (Admin)
     *
     * View all products, including inactive or deleted ones.
     */
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

    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = Product::create($request->validated());

        return (new AdminProductResource($product))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateProductRequest $request, Product $product): AdminProductResource
    {
        $this->authorize('update', $product);

        $product->update($request->validated());

        return new AdminProductResource($product);
    }

    public function destroy(Product $product): Response
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->noContent();
    }

    /**
     * Restore a deleted product
     */
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
