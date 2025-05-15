<?php

namespace App\Http\Controllers\Cart;

use Illuminate\Http\JsonResponse;
use App\Services\Cart\CartService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Cart\CartResource;
use App\Http\Requests\Cart\BaseCartRequest;
use App\Http\Requests\Cart\SetCartProductRequest;
use App\Http\Requests\Cart\ApplyPromocodeRequest;

class CartController extends Controller
{
    public function __construct(
        protected readonly CartService $cartService,
    )
    {
    }

    public function show(BaseCartRequest $request): JsonResponse
    {
        $cartData = $this->cartService->getCartData($request->getFingerprint());

        return new JsonResponse(
            CartResource::make($cartData),
        );
    }

    public function setProduct(SetCartProductRequest $request): JsonResponse
    {
        $this->cartService->setProduct(
            $request->getFingerprint(),
            $request->toDTO()
        );

        $cart = $this->cartService->getCartData($request->getFingerprint());

        return new JsonResponse(
            CartResource::make($cart),
        );
    }

    public function applyPromocode(ApplyPromocodeRequest $request): JsonResponse
    {
        $cart = $this->cartService->applyPromocode(
            $request->getFingerprint(),
            $request->input('promocode')
        );

        return new JsonResponse(
            CartResource::make($cart),
        );
    }
}
