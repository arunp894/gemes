<?php

namespace Database\Factories;

use App\Models\CountryOfOrigin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CountryOfOrigin>
 */
class CountryOfOriginFactory extends Factory
{
    protected $model = CountryOfOrigin::class;

    public function definition(): array
    {
        return [
            'name'   => fake()->unique()->country(),
            'status' => true,
        ];
    }
}
