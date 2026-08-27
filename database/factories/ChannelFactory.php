<?php

namespace Database\Factories;

use App\Models\Channel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Channel>
 */
class ChannelFactory extends Factory
{
    protected $model = Channel::class;

    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word());

        return [
            'name'   => $name,
            'code'   => strtolower($name) . '-' . fake()->unique()->numberBetween(1000, 999999),
            'status' => true,
        ];
    }

    public function pos(): static
    {
        return $this->state(fn () => ['name' => 'POS', 'code' => Channel::CODE_POS]);
    }

    public function website(): static
    {
        return $this->state(fn () => ['name' => 'Website', 'code' => Channel::CODE_WEBSITE]);
    }
}
