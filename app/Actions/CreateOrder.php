<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Events\OrderPlaced;
use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateOrder
{
    public function execute(User $user, array $data): Order
    {
        // Laravel may safely retry this closure after a deadlock because it
        // contains only transactional database work. OrderPlaced is deferred
        // until commit, so failed attempts cannot trigger external work.
        return DB::transaction(function () use ($user, $data) {
            $itemData = collect($data['items']);
            $productIds = $itemData
                ->pluck('product_id')
                ->map(fn (mixed $id): int => (int) $id);

            $duplicateProductIds = $productIds->duplicates()->unique()->values();

            if ($duplicateProductIds->isNotEmpty()) {
                throw ApiException::duplicateProducts($duplicateProductIds->all());
            }

            $this->ensurePaymentMethodIsAvailable((int) $data['payment_method_id']);
            $products = $this->availableProducts($productIds);

            $unavailableProductIds = $productIds->diff($products->keys())->values();

            if ($unavailableProductIds->isNotEmpty()) {
                throw ApiException::productsUnavailable($unavailableProductIds->all());
            }

            $this->ensureStockIsAvailable($itemData, $products);

            $totalPrice = 0;
            $pivotData = [];

            foreach ($itemData as $item) {
                $product = $products->get((int) $item['product_id']);

                $quantity = (int) $item['quantity'];
                $unitPrice = $product->price;

                $totalPrice += $unitPrice * $quantity;

                $pivotData[$product->id] = [
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ];

                // Every quantity was checked before this loop. The product
                // rows remain exclusively locked, so these decrements cannot
                // race another checkout and will roll back with the order.
                $product->decrement('stock', $quantity);
            }

            $order = Order::create([
                'user_id' => $user->id,
                'payment_method_id' => (int) $data['payment_method_id'],
                'status' => OrderStatus::Pending,
                'total_price' => $totalPrice,
            ]);

            $order->products()->attach($pivotData);

            OrderPlaced::dispatch($order);

            return $order;
        }, attempts: 3);
    }

    /**
     * Recheck and lock the payment method inside the order transaction.
     *
     * Validation may have passed before an administrator disables the method.
     * A shared lock keeps its active state stable until commit while allowing
     * other checkouts to use the same payment method concurrently. A writer
     * attempting to disable the method must wait for those readers to finish.
     */
    private function ensurePaymentMethodIsAvailable(int $paymentMethodId): void
    {
        $paymentMethod = PaymentMethod::query()
            ->whereKey($paymentMethodId)
            ->where('is_active', true)
            ->sharedLock()
            ->first();

        if ($paymentMethod === null) {
            throw ApiException::paymentMethodUnavailable($paymentMethodId);
        }
    }

    /**
     * Recheck and lock checkout products inside the transaction.
     *
     * Request validation gives useful client errors, but product availability
     * can change before this action runs. These locks keep eligibility stable
     * and protect the stock checks and decrements performed below.
     *
     * @param  Collection<int, int>  $productIds
     * @return Collection<int, Product>
     */
    private function availableProducts(Collection $productIds): Collection
    {
        return Product::query()
            ->whereKey($productIds)
            ->where('is_active', true)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * Validate every item before decrementing any product.
     *
     * Reporting the complete set lets clients repair the checkout in one
     * response. Because the product rows are already locked, these stock
     * values cannot change between this check and the later decrements.
     *
     * @param  Collection<int, array{product_id: int, quantity: int}>  $itemData
     * @param  Collection<int, Product>  $products
     */
    private function ensureStockIsAvailable(Collection $itemData, Collection $products): void
    {
        $insufficientItems = $itemData
            ->map(function (array $item) use ($products): ?array {
                $product = $products->get((int) $item['product_id']);
                $requestedQuantity = (int) $item['quantity'];

                if ($requestedQuantity <= $product->stock) {
                    return null;
                }

                return [
                    'product_id' => $product->id,
                    'requested_quantity' => $requestedQuantity,
                    'available_stock' => $product->stock,
                ];
            })
            ->filter()
            ->values();

        if ($insufficientItems->isNotEmpty()) {
            throw ApiException::insufficientStock($insufficientItems->all());
        }
    }
}
