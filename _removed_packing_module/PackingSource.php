<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Input side of a packing -- one row per raw purchase_products row drawn
 * on, and how much of it this packing claimed. See Packing /
 * PackingService.
 */
class PackingSource extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'packing_id',
        'purchase_product_id',
        'qty_taken',
        // Per-row "show on website" + Selling Price, applied to the
        // Product/output row this source creates -- see
        // PackingService::syncSources()/syncOutputsFromSources().
        'website_enabled',
        'website_price',
    ];

    protected $casts = [
        'qty_taken'       => 'integer',
        'website_enabled' => 'boolean',
        'website_price'   => 'decimal:2',
    ];

    public function packing(): BelongsTo
    {
        return $this->belongsTo(Packing::class);
    }

    public function purchaseProduct(): BelongsTo
    {
        return $this->belongsTo(PurchaseProduct::class);
    }
}
