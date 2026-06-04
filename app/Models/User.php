<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use App\Models\Location;

/**
 * Authenticated user.
 *
 * Roles are many-to-many. Effective permissions are the union of every
 * assigned role's permissions, except when the user holds a super role
 * (Role::is_super = true) — those users bypass every permission check.
 *
 * Permission lookups are cached on the model instance for the duration
 * of the request so repeated calls in views and middleware don't re-query.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * In-memory cache for the resolved permission-slug set.
     * Invalidated by flushPermissionCache() after role/permission changes.
     */
    protected ?Collection $cachedPermissionSlugs = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class)->withTimestamps();
    }

    // -----------------------------------------------------------------
    // Role checks
    // -----------------------------------------------------------------

    /**
     * Does the user hold the given role slug?
     */
    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    /**
     * Does the user hold ANY of the given role slugs?
     * Accepts a list or a pipe-delimited string ("admin|manager").
     */
    public function hasAnyRole(array|string $slugs): bool
    {
        $slugs = is_array($slugs) ? $slugs : explode('|', $slugs);

        return $this->roles->whereIn('slug', $slugs)->isNotEmpty();
    }

    /**
     * Does the user hold any role flagged as super?
     * Super users bypass every permission check.
     */
    public function isSuperAdmin(): bool
    {
        return $this->roles->where('is_super', true)->isNotEmpty();
    }

    // -----------------------------------------------------------------
    // Permission checks
    // -----------------------------------------------------------------

    /**
     * Resolve the full set of permission slugs granted to this user.
     * Cached on the instance for the request lifetime.
     */
    public function permissionSlugs(): Collection
    {
        if ($this->cachedPermissionSlugs !== null) {
            return $this->cachedPermissionSlugs;
        }

        $this->loadMissing('roles.permissions');

        return $this->cachedPermissionSlugs = $this->roles
            ->flatMap(fn (Role $role) => $role->permissions->pluck('slug'))
            ->unique()
            ->values();
    }

    /**
     * Does the user have the given permission slug?
     * Super-admins always return true.
     */
    public function hasPermission(string $slug): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->permissionSlugs()->contains($slug);
    }

    /**
     * Does the user have ANY of the given permission slugs?
     * Accepts a list or a pipe-delimited string ("products.create|products.edit").
     */
    public function hasAnyPermission(array|string $slugs): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $slugs = is_array($slugs) ? $slugs : explode('|', $slugs);

        return $this->permissionSlugs()->intersect($slugs)->isNotEmpty();
    }

    /**
     * Does the user have ALL of the given permission slugs?
     */
    public function hasAllPermissions(array $slugs): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return collect($slugs)->every(fn ($slug) => $this->permissionSlugs()->contains($slug));
    }

    /**
     * Clear the per-request permission cache.
     * Call this after assigning/revoking roles or permissions inside the
     * same request lifecycle (e.g. an admin granting a role on the fly).
     */
    public function flushPermissionCache(): void
    {
        $this->cachedPermissionSlugs = null;
        $this->unsetRelation('roles');
    }
}
