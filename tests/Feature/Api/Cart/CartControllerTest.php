<?php

namespace Tests\Feature\Api\Cart;

use Tests\TestCase;
use App\Models\Product\Product;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $fingerprint = 'test-fingerprint';

    public function test_show_empty_cart(): void
    {
        $response = $this->getJson(route('cart.show', ['fingerprint' => $this->fingerprint]));

        $response->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('fingerprint', $this->fingerprint)
                    ->where('price_before_discount', 0)
                    ->where('total_price', 0)
                    ->has('products', 0)
            );
    }

    public function test_add_product_to_cart(): void
    {
        $product = Product::factory()->create(['price' => 10000]);

        $response = $this->putJson(route('cart.set-product'), [
            'fingerprint' => $this->fingerprint,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('fingerprint', $this->fingerprint)
                    ->where('price_before_discount', 20000)
                    ->where('total_price', 20000)
                    ->has('products', 1)
                    ->has('products.0', fn ($json) =>
                        $json->where('id', $product->id)
                            ->where('name', $product->name)
                            ->where('quantity', 2)
                            ->where('price', 10000)
                    )
            );
    }

    public function test_update_product_quantity(): void
    {
        $product = Product::factory()->create(['price' => 10000]);

        $this->putJson(route('cart.set-product'), [
            'fingerprint' => $this->fingerprint,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->putJson(route('cart.set-product'), [
            'fingerprint' => $this->fingerprint,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('price_before_discount', 30000)
            ->assertJsonPath('products.0.id', $product->id)
            ->assertJsonPath('products.0.name', $product->name)
            ->assertJsonPath('products.0.quantity', 3);
    }

    public function test_remove_product_from_cart(): void
    {
        $product = Product::factory()->create(['price' => 10000]);

        $this->putJson(route('cart.set-product'), [
            'fingerprint' => $this->fingerprint,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->putJson(route('cart.set-product'), [
            'fingerprint' => $this->fingerprint,
            'product_id' => $product->id,
            'quantity' => 0,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('price_before_discount', 0)
            ->assertJsonPath('total_price', 0)
            ->assertJsonCount(0, 'products');
    }

    public function test_apply_promocode(): void
    {
        Config::set('promo-codes', [
            'ILOVETANUKI' => [
                'discount_type' => 'percent',
                'discount_amount' => 20,
            ],
        ]);

        $product = Product::factory()->create(['price' => 10000]);

        // Добавляем товар
        $this->putJson(route('cart.set-product'), [
            'fingerprint' => $this->fingerprint,
            'product_id' => $product->id,
            'quantity' => 15,
        ]);

        // Применяем промокод
        $response = $this->postJson(route('cart.apply-promocode'), [
            'fingerprint' => $this->fingerprint,
            'promocode' => 'ILOVETANUKI',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('price_before_discount', 150000)
            ->assertJsonPath('total_price', 120000);
    }

    public function test_apply_invalid_promocode(): void
    {
        $response = $this->postJson(route('cart.apply-promocode'), [
            'fingerprint' => $this->fingerprint,
            'promocode' => 'INVALID_CODE',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['promocode']);
    }

    public function test_discount_by_sum(): void
    {
        Config::set('discounts', [
            [
                'discount_type' => 'percent',
                'discount_amount' => 10,
                'when_price_more' => 200000, // 2000 рублей
            ]
        ]);

        $product = Product::factory()->create(['price' => 10000]); // 100 рублей

        // Добавляем товар на сумму больше 2000 рублей
        $response = $this->putJson(route('cart.set-product'), [
            'fingerprint' => $this->fingerprint,
            'product_id' => $product->id,
            'quantity' => 25, // 2500 рублей
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('price_before_discount', 250000)
            ->assertJsonPath('total_price', 225000); // 250000 - 10%
    }

    public function test_highest_discount_applies_when_both_available(): void
    {
        Config::set('discounts', [
            [
                'discount_type' => 'percent',
                'discount_amount' => 10,
                'when_price_more' => 200000, // 2000 рублей
            ]
        ]);

        Config::set('promo-codes', [
            'ILOVETANUKI' => [
                'discount_type' => 'percent',
                'discount_amount' => 20,
            ],
        ]);

        $product = Product::factory()->create(['price' => 10000]); // 100 рублей

        // Добавляем товар на сумму больше 2000 рублей
        $this->putJson(route('cart.set-product'), [
            'fingerprint' => $this->fingerprint,
            'product_id' => $product->id,
            'quantity' => 25, // 2500 рублей
        ]);

        // Применяем промокод с большей скидкой (20%)
        $response = $this->postJson(route('cart.apply-promocode'), [
            'fingerprint' => $this->fingerprint,
            'promocode' => 'ILOVETANUKI',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('price_before_discount', 250000) // 25 * 10000
                ->assertJsonPath('total_price', 200000); // 250000 - 20%
    }
}
