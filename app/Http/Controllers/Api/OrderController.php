<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateOrder;
use App\Http\Controllers\Controller;
use App\Http\Documentation\ApiResponseExamples;
use App\Http\Requests\Api\PaginationRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;

/**
 * @group User Orders
 */
#[Authenticated]
#[ScribeResponse(ApiResponseExamples::UNAUTHENTICATED, 401, 'A valid access token is required.')]
class OrderController extends Controller
{
    use AuthorizesRequests;

    /**
     * List my orders
     *
     * Returns a paginated list of orders belonging to the authenticated user.
     */
    #[ScribeResponse(ApiResponseExamples::ORDER_COLLECTION, 200, 'Customer orders retrieved.')]
    #[ScribeResponse(ApiResponseExamples::VALIDATION_FAILED, 422, 'The query parameters are invalid.')]
    public function index(PaginationRequest $request): AnonymousResourceCollection
    {
        $orders = $request->user()
            ->orders()
            ->with('products')
            ->latest()
            ->paginate($request->perPage());

        return OrderResource::collection($orders);
    }

    /**
     * Create a new order (Checkout)
     *
     * Validates products, calculates totals on the server, and creates the order.
     */
    #[ScribeResponse(ApiResponseExamples::ORDER, 201, 'Order created.')]
    #[ScribeResponse(ApiResponseExamples::VALIDATION_FAILED, 422, 'The checkout data is invalid.')]
    public function store(StoreOrderRequest $request, CreateOrder $createOrder): JsonResponse
    {
        $this->authorize('create', Order::class);

        $order = $createOrder->execute($request->user(), $request->validated());

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
