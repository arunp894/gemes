<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word()) . ' Stone';

        return [
            'name'         => $name,
            'code'         => strtoupper(fake()->unique()->lexify('CAT???')),
            'status'       => true,
            'is_gemstone'  => true,
            'display_order' => 0,
        ];
    }

    public function notGemstone(): static
    {
        return $this->state(fn () => ['is_gemstone' => false]);
    }
}
