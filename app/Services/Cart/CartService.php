<?php

namespace App\Services\Cart;

use Illuminate\Support\Arr;
use App\Models\Product\Product;
use App\Services\Cart\DTO\CartDTO;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelData\DataCollection;
use App\Services\Cart\DTO\CartProductDTO;
use App\Services\Cart\DTO\SetCartProductDTO;

class CartService
{
    public function getCartData(string $fingerprint): CartDTO
    {
        $dtoProducts = $this->mapCacheProductsToCartDTOCollection($fingerprint);

        $totalPrice = $dtoProducts->reduce(function (int $acc, CartProductDTO $product) {
            return $acc + ($product->price * $product->quantity);
        }, 0);

        $bySumPercent = $this->getBySumPercentDiscount($totalPrice);
        $promoCodePercent = $this->getPromocodePercentDiscount($fingerprint);

        $percentDiscount = max($promoCodePercent, $bySumPercent);

        return new CartDTO(
            $fingerprint,
            $dtoProducts,
            $totalPrice,
            intval($totalPrice - ($totalPrice / 100 * $percentDiscount)),
        );
    }

    /**
     * @return DataCollection<CartProductDTO>
     */
    protected function mapCacheProductsToCartDTOCollection(string $fingerprint): DataCollection
    {
        $cartProductsData = $this->getCartProducts($fingerprint);

        $dbProducts = Product::query()
            ->whereIn('id', array_keys($cartProductsData))
            ->get();

        $dtoProducts = new DataCollection(CartProductDTO::class, []);

        $dbProducts->each(function (Product $product) use ($cartProductsData, $dtoProducts, $dbProducts) {
            $quantity = (int) Arr::get($cartProductsData, $product->id, 0);

            $dtoProducts[] = new CartProductDTO(
                $product->id,
                $product->name,
                $quantity,
                $product->price
            );
        });

        return $dtoProducts;
    }

    public function getBySumPercentDiscount(int $totalPrice): int
    {
        $allDiscounts = config('discounts', []);

        $actualDiscount = Arr::first($allDiscounts, function (array $discountItem) use ($totalPrice) {
            return $totalPrice > Arr::get($discountItem, 'when_price_more');
        });

        return Arr::get($actualDiscount, 'discount_amount', 0);
    }

    public function getPromocodePercentDiscount(string $fingerprint): int
    {
        $promoCode = $this->getCartPromoCode($fingerprint);

        return Arr::get(config('promo-codes'), "$promoCode.discount_amount", 0);
    }

    public function setProduct(string $fingerPrint, SetCartProductDTO $data): array
    {
        $cartProducts = $this->getCartProducts($fingerPrint);

        if ($data->quantity === 0) {
            Arr::forget($cartProducts, $data->product_id);
        } else {
            Arr::set($cartProducts, $data->product_id, $data->quantity);
        }

        $this->setCartProducts($fingerPrint, $cartProducts);

        return $cartProducts;
    }

    public function applyPromocode(string $fingerprint, string $promoCode): CartDTO
    {
        $this->setCartPromoCode($fingerprint, $promoCode);

        return $this->getCartData($fingerprint);
    }

    public function getCartPromoCode(string $fingerPrint): ?string
    {
        return Cache::get('cart:' . $fingerPrint . ':promocode');
    }

    public function setCartPromoCode(string $fingerPrint, string $promoCode): void
    {
        Cache::put('cart:' . $fingerPrint . ':promocode', $promoCode);
    }

    public function getCartProducts(string $fingerPrint): array
    {
        return Cache::get('cart:' . $fingerPrint . ':products', []);
    }

    public function setCartProducts(string $fingerPrint, array $products): void
    {
        Cache::put('cart:' . $fingerPrint . ':products', $products);
    }

}
