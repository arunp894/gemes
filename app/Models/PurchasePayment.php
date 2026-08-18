<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Purchase payment row.
 *
 * One purchase can have many payments — partial / advance payments and
 * split-tender (e.g. half cash + half bank transfer) to a supplier are
 * first-class. Supplier refunds/credits insert NEGATIVE amounts so
 * SUM(amount) stays a correct paid-balance. Mirrors SalePayment.
 *
 * The Purchase header's paid_amount / due_amount / payment_status are
 * denormalized aggregates and recomputed by PurchaseService whenever
 * any payment changes.
 */
class PurchasePayment extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const METHOD_CASH          = 'cash';
    public const METHOD_CARD          = 'card';
    public const METHOD_UPI           = 'upi';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_CHEQUE        = 'cheque';
    public const METHOD_OTHER         = 'other';

    public const METHODS = [
        self::METHOD_CASH          => 'Cash',
        self::METHOD_CARD          => 'Card',
        self::METHOD_UPI           => 'UPI',
        self::METHOD_BANK_TRANSFER => 'Bank Transfer',
        self::METHOD_CHEQUE        => 'Cheque',
        self::METHOD_OTHER         => 'Other',
    ];

    protected $fillable = [
        'purchase_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $p) {
            if (auth()->check() && empty($p->created_by)) {
                $p->created_by = auth()->id();
            }
        });
    }

    /* ─── Relationships ────────────────────────────────────── */

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ─── Helpers ──────────────────────────────────────────── */

    public function methodLabel(): string
    {
        return self::METHODS[$this->payment_method] ?? ucfirst((string) $this->payment_method);
    }

    public function methodBadgeClass(): string
    {
        return match ($this->payment_method) {
            self::METHOD_CASH          => 'badge-soft-success',
            self::METHOD_CARD          => 'badge-soft-primary',
            self::METHOD_UPI           => 'badge-soft-info',
            self::METHOD_BANK_TRANSFER => 'badge-soft-purple',
            self::METHOD_CHEQUE        => 'badge-soft-warning',
            default                    => 'badge-soft-secondary',
        };
    }

    public function isRefund(): bool
    {
        return (float) $this->amount < 0;
    }
}
