<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Stock transfer line — one row per piece being moved.
 *
 * Per-piece convention: each row references an exact `purchase_products`
 * row, so when the transfer is posted the OUT and IN movements can be
 * filed against the right ledger key. product_id always names the
 * piece's Product directly.
 */
class StockTransferLine extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'stock_transfer_id',
        'purchase_product_id',
        'product_id',
        'qty',
        'carat_weight',
        'to_rack_id',
        'notes',
    ];

    protected $casts = [
        'qty'          => 'integer',
        'carat_weight' => 'decimal:3',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseProduct(): BelongsTo
    {
        return $this->belongsTo(PurchaseProduct::class, 'purchase_product_id');
    }

    public function toRack(): BelongsTo
    {
        return $this->belongsTo(Rack::class, 'to_rack_id');
    }
}
