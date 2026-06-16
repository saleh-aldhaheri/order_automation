<?php

namespace Database\Factories;

use App\Models\ExternalSystem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<ExternalSystem>
 */
class ExternalSystemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'system_name' => fake()->unique()->name(),
            'client_id' => fake()->unique()->uuid(),
            'client_secret' => Hash::make('password'),
        ];
    }
}
