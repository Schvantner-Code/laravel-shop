<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderIndexRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Group;

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
    public function index(OrderIndexRequest $request)
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::with(['user', 'products', 'paymentMethod']);

        // filters
        $query->when($request->validated('user_id'), fn ($q, $id) => $q->where('user_id', $id));

        $query->when($request->validated('product_id'), function ($q, $id) {
            $q->whereHas('products', fn ($subQ) => $subQ->where('products.id', $id));
        });

        // sorting
        $sortColumn = $request->validated('sort_by', 'created_at');
        $sortDir = $request->validated('sort_dir', 'desc');

        $query->orderBy($sortColumn, $sortDir);

        return OrderResource::collection($query->paginate($request->perPage(20)));
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
            throw ApiException::invalidOrderTransition($order->status->value, $newStatus->value);
        }

        $order->update(['status' => $newStatus]);

        return new OrderResource($order);
    }
}
