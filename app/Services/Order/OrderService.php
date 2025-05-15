<?php

namespace App\Services\Order;

use Throwable;
use App\Models\Order\Order;
use App\Services\Cart\DTO\CartDTO;
use Illuminate\Support\Facades\DB;
use App\Models\Order\OrderProduct;
use App\Services\Cart\DTO\CartProductDTO;

class OrderService
{
    public function store(CartDTO $cartData, string $phone)
    {
        DB::beginTransaction();

        try {
            $order = Order::create([
                'phone' => $phone,
                'price' => $cartData->total_price,
                'fingerprint' => $cartData->fingerprint,
            ]);

            foreach ($cartData->products as $product) {
                /** @var CartProductDTO $product */

                OrderProduct::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $product->quantity,
                    'price_per_unit' => $product->price,
                    'total_price' => $product->price * $product->quantity,
                ]);
            }

            DB::commit();

            return $order;
        } catch (Throwable $throwable) {
            DB::rollBack();

            throw $throwable;
        }
    }
}
