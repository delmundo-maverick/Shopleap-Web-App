<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'seller_id' => User::factory(),
            'name' => fake()->words(3, true),
            'category' => config('shopleap_categories.list')[0]['name'],
            'price' => 100,
            'stock_quantity' => 10,
            'status' => Product::STATUS_DRAFT,
            'low_stock_threshold' => 5,
        ];
    }
}
