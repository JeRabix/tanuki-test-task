<?php

namespace App\Services\Cart\DTO;

use Spatie\LaravelData\Data;

class CartProductDTO extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public int $quantity,
        public int $price,
    )
    {
    }
}
