<?php

namespace Tests\Feature\Api\Order;

use Tests\TestCase;
use App\Models\Order\Order;
use App\Models\Product\Product;
use App\Models\Order\OrderProduct;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $fingerprint = 'test-fingerprint';
    protected string $validPhoneNumber = '+79991234567';

    public function test_create_order_with_valid_data(): void
    {
        $product = Product::factory()->create(['price' => 120000]); // 1200 рублей

        $this->putJson(route('cart.set-product'), [
            'fingerprint' => $this->fingerprint,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // Создаем заказ
        $response = $this->postJson(route('orders.store'), [
            'fingerprint' => $this->fingerprint,
            'phone' => $this->validPhoneNumber,
        ]);

        $response->assertStatus(200)
            ->assertJson(fn(AssertableJson $json) => $json->has('id')
                ->where('phone', $this->validPhoneNumber)
                ->where('price', 120000)
                ->etc()
            );

        $this->assertDatabaseHas(Order::class, [
            'phone' => $this->validPhoneNumber,
            'fingerprint' => $this->fingerprint,
            'price' => 120000,
        ]);

        $order = Order::query()->findOrFail($response->json('id'));

        $this->assertDatabaseHas(OrderProduct::class, [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price_per_unit' => $product->price,
            'total_price' => $product->price,
        ]);
    }

    public function test_create_order_with_invalid_phone(): void
    {
        $product = Product::factory()->create(['price' => 120000]);

        $this->putJson(route('cart.set-product'), [
            'fingerprint' => $this->fingerprint,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // Пытаемся создать заказ с невалидным номером телефона
        $response = $this->postJson(route('orders.store'), [
            'fingerprint' => $this->fingerprint,
            'phone' => '123456', // не соответствует формату российского номера
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_create_order_with_empty_cart(): void
    {
        // Пытаемся создать заказ с пустой корзиной
        $response = $this->postJson(route('orders.store'), [
            'fingerprint' => $this->fingerprint,
            'phone' => $this->validPhoneNumber,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_create_order_with_price_below_minimum(): void
    {
        // Устанавливаем минимальную стоимость заказа
        config(['orders.min_order_price' => 100000]); // 1000 рублей

        $product = Product::factory()->create(['price' => 50000]); // 500 рублей

        // Добавляем товар в корзину на сумму меньше минимальной
        $this->putJson(route('cart.set-product'), [
            'fingerprint' => $this->fingerprint,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // Пытаемся создать заказ
        $response = $this->postJson(route('orders.store'), [
            'fingerprint' => $this->fingerprint,
            'phone' => $this->validPhoneNumber,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_create_order_with_discount(): void
    {
        // Настройка скидки для заказов больше 2000 рублей
        config([
            'discounts' => [
                [
                    'discount_type' => 'percent',
                    'discount_amount' => 10,
                    'when_price_more' => 200000, // 2000 рублей
                ]
            ]
        ]);

        $product = Product::factory()->create(['price' => 100000]); // 1000 рублей

        // Добавляем товар в корзину на сумму больше 2000 рублей
        $this->putJson(route('cart.set-product'), [
            'fingerprint' => $this->fingerprint,
            'product_id' => $product->id,
            'quantity' => 3, // 3000 рублей
        ]);

        $response = $this->postJson(route('orders.store'), [
            'fingerprint' => $this->fingerprint,
            'phone' => $this->validPhoneNumber,
        ]);

        $response->assertStatus(200)
            ->assertJson(fn(AssertableJson $json) => $json->has('id')
                ->where('price', 270000) // 3000 рублей - 10%
                ->etc()
            );

        $this->assertDatabaseHas(Order::class, [
            'phone' => $this->validPhoneNumber,
            'fingerprint' => $this->fingerprint,
            'price' => 270000,
        ]);

        $order = Order::query()->findOrFail($response->json('id'));

        $this->assertDatabaseHas(OrderProduct::class, [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'price_per_unit' => $product->price,
            'total_price' => $product->price * 3,
        ]);
    }

    public function test_create_order_with_promocode(): void
    {
        // Настройка промокода
        Config::set('promo-codes', [
            'ILOVETANUKI' => [
                'discount_type' => 'percent',
                'discount_amount' => 20,
            ]
        ]);

        $product = Product::factory()->create(['price' => 150000]); // 1500 рублей

        $this->putJson(route('cart.set-product'), [
            'fingerprint' => $this->fingerprint,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->postJson(route('cart.apply-promocode'), [
            'fingerprint' => $this->fingerprint,
            'promocode' => 'ILOVETANUKI',
        ]);

        $response = $this->postJson(route('orders.store'), [
            'fingerprint' => $this->fingerprint,
            'phone' => $this->validPhoneNumber,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('price', 120000); // 1500 рублей - 20%

        $this->assertDatabaseHas(Order::class, [
            'phone' => $this->validPhoneNumber,
            'fingerprint' => $this->fingerprint,
            'price' => 120000,
        ]);

        $order = Order::query()->findOrFail($response->json('id'));

        $this->assertDatabaseHas(OrderProduct::class, [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price_per_unit' => $product->price,
            'total_price' => $product->price,
        ]);
    }
}
