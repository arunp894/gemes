<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalPayment extends Model
{
    use SoftDeletes;

    public const TYPE_RENT    = 'rent';
    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_REFUND  = 'refund';

    protected $fillable = ['rental_id', 'type', 'payment_method', 'amount', 'payment_date', 'note', 'created_by'];

    protected $casts = ['amount' => 'decimal:2', 'payment_date' => 'date'];

    public function rental(): BelongsTo { return $this->belongsTo(Rental::class); }
}
