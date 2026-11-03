<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalLog extends Model
{
    protected $fillable = ['rental_id', 'user_id', 'action', 'detail'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
