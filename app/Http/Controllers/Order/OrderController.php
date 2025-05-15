<?php

namespace App\Http\Controllers\Order;

use Illuminate\Http\JsonResponse;
use App\Services\Cart\CartService;
use App\Http\Controllers\Controller;
use App\Services\Order\OrderService;
use App\Http\Resources\Order\OrderResource;
use App\Http\Requests\Order\StoreOrderRequest;

class OrderController extends Controller
{
    public function __construct(
        protected readonly OrderService $orderService,
        protected readonly CartService  $cartService,
    )
    {
    }

    public function store(StoreOrderRequest $request)
    {
        $cart = $this->cartService->getCartData($request->getFingerprint());

        $order = $this->orderService->store($cart, $request->input('phone'));

        return new JsonResponse(
            OrderResource::make($order)
        );
    }
}
