<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** One audit entry per edit of a purchase (see the purchase_edit_logs migration). */
class PurchaseEditLog extends Model
{
    use SoftDeletes;

    protected $fillable = ['purchase_id', 'user_id', 'action', 'changes', 'ip_address'];

    protected $casts = ['changes' => 'array'];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
