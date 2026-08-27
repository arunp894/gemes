<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word()) . ' Role';

        return [
            'name'        => $name,
            'slug'        => Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 999999),
            'description' => fake()->sentence(),
            'is_super'    => false,
        ];
    }

    /**
     * A super-admin role bypasses every permission check regardless of
     * which (if any) permissions are attached to it.
     */
    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'name'     => 'Super Admin',
            'is_super' => true,
        ]);
    }
}
