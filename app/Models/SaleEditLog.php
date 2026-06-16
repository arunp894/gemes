<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SaleEditLog — a single audit entry recording an edit to a Sale.
 *
 * Written by SaleService::update() after a successful change. Holds the
 * editing user, a field-level diff (`changes`), and request context so
 * the show page can render a "who changed what, when" history.
 */
class SaleEditLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_id',
        'user_id',
        'action',
        'changes',
        'ip_address',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** The user who performed the edit. */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
