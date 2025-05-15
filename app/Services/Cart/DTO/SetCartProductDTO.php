<?php

namespace App\Services\Cart\DTO;

use Spatie\LaravelData\Data;

class SetCartProductDTO extends Data
{
    public int $product_id;
    public int $quantity;
}
