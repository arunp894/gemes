<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Role.
 *
 * Roles aggregate permissions and are assigned to users. A role flagged
 * as `is_super` grants every action — used for the seeded `admin` role.
 *
 * Slug is the immutable identifier used in code (middleware, seeders).
 * Name is the human label shown in the UI.
 */
class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_super',
    ];

    protected function casts(): array
    {
        return [
            'is_super' => 'boolean',
        ];
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Does this role grant the given permission slug?
     * Super roles always return true.
     */
    public function hasPermission(string $slug): bool
    {
        if ($this->is_super) {
            return true;
        }

        return $this->permissions->contains('slug', $slug);
    }

    /**
     * Replace the role's permission set with the given slugs.
     * Used by the role-edit form.
     */
    public function syncPermissionsBySlug(array $slugs): void
    {
        $ids = Permission::whereIn('slug', $slugs)->pluck('id');
        $this->permissions()->sync($ids);
    }
}
