<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Package;
use App\Models\Shop;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed shops, each with several orders, each order with a few packages.
     */
    public function run(): void
    {
        Shop::factory()
            ->count(3)
            ->create()
            ->each(function (Shop $shop) {
                Order::factory()
                    ->count(fake()->numberBetween(5, 12))
                    ->forShop($shop)
                    ->create()
                    ->each(function (Order $order) {
                        Package::factory()
                            ->count(fake()->numberBetween(1, 3))
                            ->forOrder($order)
                            ->create();
                    });
            });
    }
}
