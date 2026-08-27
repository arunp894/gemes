<?php

namespace Tests\Concerns;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

/**
 * Test helpers for building users with a specific, minimal set of
 * permissions rather than depending on the full app seeders — keeps
 * each test's access grant explicit and independent of seeder changes.
 */
trait InteractsWithPermissions
{
    /**
     * Create (and log in as) a user holding exactly the given permission
     * slugs, via a freshly-made role. No seeders required.
     */
    protected function actingAsUserWithPermissions(array $slugs = [], array $userAttributes = []): User
    {
        $user = User::factory()->create($userAttributes);

        if ($slugs !== []) {
            $role = Role::factory()->create();

            foreach ($slugs as $slug) {
                $permission = Permission::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name'   => ucfirst(str_replace(['.', '-'], ' ', $slug)),
                        'module' => explode('.', $slug)[0] ?? $slug,
                    ]
                );
                $role->permissions()->attach($permission->id);
            }

            $user->roles()->attach($role->id);
        }

        $this->actingAs($user);

        return $user;
    }

    /**
     * Create (and log in as) a super-admin user who bypasses every
     * permission check regardless of attached permissions.
     */
    protected function actingAsSuperAdmin(array $userAttributes = []): User
    {
        $user = User::factory()->create($userAttributes);
        $role = Role::factory()->superAdmin()->create();
        $user->roles()->attach($role->id);

        $this->actingAs($user);

        return $user;
    }
}
