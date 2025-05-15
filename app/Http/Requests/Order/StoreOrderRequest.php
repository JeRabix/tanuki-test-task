<?php

namespace App\Http\Requests\Order;

use App\Services\Cart\CartService;
use App\Http\Requests\Cart\BaseCartRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreOrderRequest extends BaseCartRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $cartService = app(CartService::class);

        $cart = $cartService->getCartData($this->getFingerprint());

        $minOrderPrice = config('orders.min_order_price');

        if ($minOrderPrice !== null && $cart->total_price <= $minOrderPrice) {
            throw ValidationException::withMessages([
                'price' => 'minimal order price - '. number_format($minOrderPrice / 100, 0, '.', ' ') .' RUB'
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'phone' => ['required', 'phone:RU'],
        ];
    }


}
