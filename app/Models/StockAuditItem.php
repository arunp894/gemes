<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One expected piece in a stock audit's frozen snapshot — one row per
 * purchase_products unit that had positive on-hand balance at the
 * audit's location when the audit started (see
 * StockService::onHandPiecesForLocation()).
 *
 * matched_at is stamped the moment a scan resolves to this row (see
 * StockAuditService::scan()); still-null rows after the audit is
 * completed are exactly the "missing stock" report.
 */
class StockAuditItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'stock_audit_id',
        'purchase_product_id',
        'product_id',
        'lot_code',
        'barcode',
        'matched_at',
        'matched_by',
        'written_off_at',
        'written_off_by',
    ];

    protected $casts = [
        'matched_at'     => 'datetime',
        'written_off_at' => 'datetime',
    ];

    /* ─── Relationships ────────────────────────────────────── */

    public function audit(): BelongsTo
    {
        return $this->belongsTo(StockAudit::class, 'stock_audit_id');
    }

    public function purchaseProduct(): BelongsTo
    {
        return $this->belongsTo(PurchaseProduct::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function matcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_by');
    }

    public function writtenOffBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'written_off_by');
    }

    /* ─── Helpers ──────────────────────────────────────────── */

    public function isMatched(): bool
    {
        return $this->matched_at !== null;
    }

    public function isMissing(): bool
    {
        return $this->matched_at === null;
    }

    public function isWrittenOff(): bool
    {
        return $this->written_off_at !== null;
    }

    /**
     * Historical rows that predate lot codes (and, much more rarely, the
     * dock barcode) have nothing a scan can ever match against — flag
     * them distinctly so the report doesn't lump "never trackable" in
     * with "physically missing".
     */
    public function isUntrackable(): bool
    {
        return empty($this->lot_code) && empty($this->barcode);
    }
}
