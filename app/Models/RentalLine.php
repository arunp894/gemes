<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalLine extends Model
{
    use SoftDeletes;

    public const STATUS_OUT      = 'out';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_LOST     = 'lost';

    protected $fillable = [
        'rental_id', 'product_id', 'purchase_product_id', 'qty', 'carat_weight',
        'rent_amount', 'deposit_amount', 'status', 'returned_at', 'deduction', 'note',
    ];

    protected $casts = [
        'carat_weight'   => 'decimal:3',
        'rent_amount'    => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'deduction'      => 'decimal:2',
        'returned_at'    => 'datetime',
    ];

    public function rental(): BelongsTo          { return $this->belongsTo(Rental::class); }
    public function product(): BelongsTo         { return $this->belongsTo(Product::class)->withTrashed(); }
    public function purchaseProduct(): BelongsTo { return $this->belongsTo(PurchaseProduct::class)->withTrashed(); }

    public function isOut(): bool { return $this->status === self::STATUS_OUT; }
}
