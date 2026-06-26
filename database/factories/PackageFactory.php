<?php

namespace Database\Factories;

use App\Enums\PackageStatusEnum;
use App\Enums\ShopsEnum;
use App\Integrations\Shopee\Enums\ShopeePackageFulfillmentStatusEnum;
use App\Models\Order;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $externalStatus = $this->faker->randomElement([
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_READY,
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_REQUEST_CREATED,
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_PICKUP_DONE,
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_DELIVERY_DONE,
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_PICKUP_RETRY,
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_NOT_START,
        ]);

        $hasTracking = in_array($externalStatus, [
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_REQUEST_CREATED,
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_PICKUP_DONE,
            ShopeePackageFulfillmentStatusEnum::LOGISTICS_DELIVERY_DONE,
        ], true);

        $packageNumber = (string) $this->faker->unique()->numerify('OFG##########');

        return [
            'order_id' => Order::factory(),
            'external_package_id' => $packageNumber,
            'external_order_id' => strtoupper($this->faker->bothify('25########??????')),
            'shop_type' => ShopsEnum::SHOPEE->value,
            'external_package_status' => $externalStatus,
            'package_status' => PackageStatusEnum::fromShopee($externalStatus)->value,
            'details' => [
                'raw_data' => [
                    'package_number' => $packageNumber,
                    'logistics_status' => $externalStatus,
                    'shipping_carrier' => $this->faker->randomElement(['Ninja Van', 'J&T Express', 'Shopee Xpress']),
                ],
                'doc_info' => [],
                'tracking_number' => $hasTracking ? [strtoupper($this->faker->bothify('SG#########??'))] : [],
            ],
        ];
    }

    /**
     * Attach the package to a specific order, keeping the denormalised columns in sync.
     */
    public function forOrder(Order $order): static
    {
        return $this->state(fn () => [
            'order_id' => $order->id,
            'external_order_id' => $order->external_order_id,
            'shop_type' => $order->shop_type->value,
        ]);
    }
}
