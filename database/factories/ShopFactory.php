<?php

namespace Database\Factories;

use App\Enums\ShopsEnum;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Shop>
 */
class ShopFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_type' => ShopsEnum::SHOPEE->value,
            'external_shop_id' => (string) $this->faker->unique()->numerify('########'),
            'is_active' => true,
            'auth_configuration' => [
                'auth' => [
                    'access_token' => [
                        'token' => Str::random(40),
                        'expired_in' => now()->addHours(4),
                    ],
                    'refresh_token' => [
                        'token' => Str::random(40),
                        'expired_in' => now()->addDays(30),
                    ],
                ],
            ],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
