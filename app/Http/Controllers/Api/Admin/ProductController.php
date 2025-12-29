<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Http\Resources\Admin\AdminProductResource;
use App\Models\Product;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;

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
    #[QueryParam('scope', 'string', "Filter list: 'active' (default), 'trashed', or 'all'.", example: 'all')]
    #[QueryParam('per_page', 'integer', 'Items per page (Max 50).', example: 10)]
    #[QueryParam('page', 'integer', 'The page number.', example: 1)]
    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::with('category');

        // Filter Logic
        $scope = $request->query('scope', 'active');

        match ($scope) {
            'trashed' => $query->onlyTrashed(), // Show ONLY deleted
            'all' => $query->withTrashed(), // Show Active + Deleted
            default => $query, // Show Active only (default)
        };

        // allow per_page up to 50
        $perPage = $request->input('per_page', 10);
        if ($perPage > 50 || $perPage < 1) {
            $perPage = 50;
        }

        return AdminProductResource::collection($query->paginate($perPage));
    }

    public function store(StoreProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $product = Product::create($request->validated());

        return new AdminProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        $product->update($request->validated());

        return new AdminProductResource($product);
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->noContent();
    }

    /**
     * Restore a deleted product
     */
    public function restore(int $id)
    {
        $product = Product::withTrashed()->findOrFail($id);

        if ($product->deleted_at === null) {
            return response()->json(['message' => 'Product is not deleted.'], 400);
        }

        $this->authorize('restore', $product);

        $product->restore();

        return new AdminProductResource($product);
    }
}
