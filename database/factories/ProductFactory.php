<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(3, true));
        $price = fake()->numberBetween(100, 300);

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(5),
            'category_id' => Category::factory(),
            'price' => $price,
            'base_price' => $price,
            'is_active' => true,
            'is_available' => true,
        ];
    }
}
