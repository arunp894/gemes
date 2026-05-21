<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Permission.
 *
 * Atomic capability identified by `slug` ("module.action"). Permissions
 * are attached to roles, not directly to users.
 *
 * The `module` column groups permissions so the role-edit screen can
 * render them as collapsible sections (Categories / Products / etc.).
 */
class Permission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'module',
        'description',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /**
     * Filter permissions belonging to a given module.
     * Used by the role-edit UI to render per-module sections.
     */
    public function scopeInModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }
}
