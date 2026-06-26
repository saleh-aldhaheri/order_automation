<?php

namespace Database\Factories;

use App\Enums\OrderStatusEnum;
use App\Enums\ShopsEnum;
use App\Integrations\Shopee\Enums\ShopeeOrderStatusEnum;
use App\Models\Order;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $externalStatus = $this->faker->randomElement(ShopeeOrderStatusEnum::cases());
        $orderSn = strtoupper($this->faker->unique()->bothify('25########??????'));

        return [
            'shop_id' => Shop::factory(),
            'external_order_id' => $orderSn,
            'external_shop_id' => (string) $this->faker->numerify('########'),
            'shop_type' => ShopsEnum::SHOPEE->value,
            'external_order_status' => $externalStatus->value,
            'order_status' => OrderStatusEnum::fromShopee($externalStatus)->value,
            'details' => [
                'raw_data' => [
                    'order_sn' => $orderSn,
                    'order_status' => $externalStatus->value,
                    'currency' => 'SGD',
                    'total_amount' => $this->faker->randomFloat(2, 5, 500),
                    'buyer_username' => $this->faker->userName(),
                    'create_time' => $this->faker->unixTime(),
                ],
            ],
        ];
    }

    /**
     * Attach the order to a specific shop, keeping the denormalised columns in sync.
     */
    public function forShop(Shop $shop): static
    {
        return $this->state(fn () => [
            'shop_id' => $shop->id,
            'external_shop_id' => $shop->external_shop_id,
            'shop_type' => $shop->shop_type->value,
        ]);
    }
}
