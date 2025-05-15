<?php

namespace App\Services\Cart\DTO;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Attributes\DataCollectionOf;

class CartDTO extends Data
{
    public function __construct(
        public string $fingerprint,
        #[DataCollectionOf(CartProductDTO::class)]
        public DataCollection $products,
        public int $price_before_discount,
        public int $total_price,
    )
    {
    }
}
