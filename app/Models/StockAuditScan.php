<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Raw log of every scan event during a stock audit — including
 * duplicates and scans that matched nothing in the snapshot. Kept
 * separate from StockAuditItem (the matched/unmatched state) so the
 * full "who scanned what, when" trail survives even an undo (soft
 * delete — see StockAuditService::undoLastScan()).
 */
class StockAuditScan extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const RESULT_MATCHED    = 'matched';
    public const RESULT_DUPLICATE  = 'duplicate';
    public const RESULT_UNEXPECTED = 'unexpected';

    public const RESULTS = [
        self::RESULT_MATCHED,
        self::RESULT_DUPLICATE,
        self::RESULT_UNEXPECTED,
    ];

    protected $fillable = [
        'stock_audit_id',
        'stock_audit_item_id',
        'scanned_value',
        'result',
        'scanned_by',
        'scanned_at',
        'notes',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $s) {
            if (empty($s->scanned_at)) {
                $s->scanned_at = now();
            }
            if (auth()->check() && empty($s->scanned_by)) {
                $s->scanned_by = auth()->id();
            }
        });
    }

    /* ─── Relationships ────────────────────────────────────── */

    public function audit(): BelongsTo
    {
        return $this->belongsTo(StockAudit::class, 'stock_audit_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(StockAuditItem::class, 'stock_audit_item_id');
    }

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    /* ─── Helpers ──────────────────────────────────────────── */

    public function isMatched():    bool { return $this->result === self::RESULT_MATCHED; }
    public function isDuplicate():  bool { return $this->result === self::RESULT_DUPLICATE; }
    public function isUnexpected(): bool { return $this->result === self::RESULT_UNEXPECTED; }

    public function resultLabel(): string
    {
        return match ($this->result) {
            self::RESULT_MATCHED    => 'Matched',
            self::RESULT_DUPLICATE  => 'Duplicate',
            self::RESULT_UNEXPECTED => 'Unexpected',
            default                 => ucfirst($this->result),
        };
    }

    public function resultBadgeClass(): string
    {
        return match ($this->result) {
            self::RESULT_MATCHED    => 'badge-soft-success',
            self::RESULT_DUPLICATE  => 'badge-soft-warning',
            self::RESULT_UNEXPECTED => 'badge-soft-danger',
            default                 => 'badge-soft-secondary',
        };
    }
}
