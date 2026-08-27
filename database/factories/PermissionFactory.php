<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $module = fake()->unique()->word();

        return [
            'name'        => ucfirst($module) . ' — View',
            'slug'        => $module . '.view',
            'module'      => $module,
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Build a permission with an explicit slug (e.g. 'sales.create'),
     * deriving the module from the part before the first dot.
     */
    public function slug(string $slug): static
    {
        return $this->state(fn () => [
            'slug'   => $slug,
            'module' => Str::before($slug, '.'),
            'name'   => ucfirst(str_replace(['.', '-'], ' ', $slug)),
        ]);
    }
}
