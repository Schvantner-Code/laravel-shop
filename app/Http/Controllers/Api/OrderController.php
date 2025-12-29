<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\QueryParam;

/**
 * @group User Orders
 */
#[Authenticated]
class OrderController extends Controller
{
    /**
     * List my orders
     *
     * Returns a paginated list of orders belonging to the authenticated user.
     */
    #[QueryParam('per_page', 'integer', 'Items per page (Max 50).', example: 10)]
    #[QueryParam('page', 'integer', 'The page number.', example: 1)]
    public function index(Request $request)
    {
        // allow per_page up to 50
        $perPage = $request->input('per_page', 10);
        if ($perPage > 50 || $perPage < 1) {
            $perPage = 50;
        }

        $orders = $request->user()
            ->orders()
            ->with('products')
            ->latest()
            ->paginate($perPage);

        return OrderResource::collection($orders);
    }

    /**
     * Create a new order (Checkout)
     *
     * Validates products, calculates totals on the server, and creates the order.
     */
    public function store(StoreOrderRequest $request, CreateOrder $createOrder)
    {
        $order = $createOrder->execute($request->user(), $request->validated());

        return new OrderResource($order);
    }
}
