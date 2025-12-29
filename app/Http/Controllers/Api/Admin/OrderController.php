<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;

#[Group('Admin Management', 'Endpoints for store administrators')]
#[Authenticated]
class OrderController extends Controller
{
    use AuthorizesRequests;

    /**
     * List all orders
     *
     * Filter by user, product, or date. Sort by price or date.
     */
    #[QueryParam('user_id', 'integer', 'Filter by Customer ID.', required: false)]
    #[QueryParam('product_id', 'integer', 'Filter by Product ID inside the order.', required: false)]
    #[QueryParam('sort_by', 'string', "Sort by 'total_price' or 'created_at'.", example: 'created_at')]
    #[QueryParam('sort_dir', 'string', "Sort direction 'asc' or 'desc'.", example: 'desc')]
    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::with(['user', 'products', 'paymentMethod']);

        // filters
        $query->when($request->query('user_id'), fn ($q, $id) => $q->where('user_id', $id));

        $query->when($request->query('product_id'), function ($q, $id) {
            $q->whereHas('products', fn ($subQ) => $subQ->where('products.id', $id));
        });

        // sorting
        $sortColumn = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc');

        // whitelist columns to prevent SQL injection or crashing
        if (in_array($sortColumn, ['total_price', 'created_at'])) {
            $query->orderBy($sortColumn, $sortDir);
        }

        return OrderResource::collection($query->paginate(20));
    }

    /**
     * Update Order Status
     *
     * Transition the order to a new status (e.g. pending -> paid).
     */
    #[BodyParam('status', 'string', 'The new status (paid, shipped, completed, cancelled).', example: 'paid')]
    public function updateStatus(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
        ]);

        $newStatus = OrderStatus::from($validated['status']);

        // enforce state machine
        if (! $order->status->canTransitionTo($newStatus, $order)) {
            return response()->json([
                'message' => "Invalid transition from {$order->status->value} to {$newStatus->value} for this payment method.",
            ], 422);
        }

        $order->update(['status' => $newStatus]);

        return new OrderResource($order);
    }
}
