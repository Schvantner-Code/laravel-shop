<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateOrder
{
    public function execute(User $user, array $data): Order
    {
        // use transaction to ensure data integrity
        return DB::transaction(function () use ($user, $data) {
            $itemData = collect($data['items']);
            $productIds = $itemData->pluck('product_id');
            $products = Product::whereIn('id', $productIds)->get();

            $totalPrice = 0;
            $pivotData = [];

            foreach ($itemData as $item) {
                $product = $products->find($item['product_id']);

                // TODO: check if the product is in stock
                // for now, we assume it is always in stock
                $quantity = $item['quantity'];

                $unitPrice = $product->price;

                $totalPrice += $unitPrice * $quantity;

                // Prepare data for the 'order_product' table
                $pivotData[$product->id] = [
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice, // Snapshot of price at purchase time
                ];
            }

            $order = Order::create([
                'user_id' => $user->id,
                'payment_method_id' => $data['payment_method_id'],
                'status' => OrderStatus::Pending,
                'total_price' => $totalPrice,
            ]);

            $order->products()->attach($pivotData);

            return $order;
        });
    }
}
