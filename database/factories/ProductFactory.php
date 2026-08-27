<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'title'       => ucfirst(fake()->unique()->words(3, true)),
            'sku'         => strtoupper(fake()->unique()->bothify('SKU-####??')),
            'category_id' => Category::factory(),
            'status'      => Product::STATUS_ACTIVE,
            'pack_type'   => Product::PACK_TYPE_PIECE,
        ];
    }

    public function websiteEnabled(): static
    {
        return $this->state(fn () => [
            'website_enabled' => true,
            'website_price'   => fake()->randomFloat(2, 100, 50000),
        ]);
    }
}
