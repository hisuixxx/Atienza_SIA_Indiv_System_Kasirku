<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_number' => 'B-' . fake()->unique()->numberBetween(1000, 9999),
            'table_number' => fake()->numberBetween(1, 20),
            'status' => fake()->randomElement(['pending', 'completed', 'cancelled']),
            'customer_name' => fake()->name(),
        ];
    }
}
