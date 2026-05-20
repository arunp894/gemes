<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Barcode extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Barcode format constants — must match the format dropdown in the UI.
     */
    public const FORMAT_EAN_13   = 'EAN-13';
    public const FORMAT_EAN_8    = 'EAN-8';
    public const FORMAT_UPC_A    = 'UPC-A';
    public const FORMAT_CODE_128 = 'Code 128';
    public const FORMAT_QR_CODE  = 'QR Code';
    public const FORMAT_CUSTOM   = 'Custom';

    /**
     * Ordered list of supported formats. Used by dropdowns + validation.
     *
     * @var array<int, string>
     */
    public const FORMATS = [
        self::FORMAT_EAN_13,
        self::FORMAT_EAN_8,
        self::FORMAT_UPC_A,
        self::FORMAT_CODE_128,
        self::FORMAT_QR_CODE,
        self::FORMAT_CUSTOM,
    ];

    /**
     * Maximum number of barcodes per product in multi-barcode mode (spec §5.2).
     */
    public const MAX_BARCODES_PER_PRODUCT = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'barcode_value',
        'barcode_format',
        'barcode_label',
        'is_primary',
        'sequence_number',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_primary'      => 'boolean',
        'sequence_number' => 'integer',
        'product_id'      => 'integer',
    ];

    /* -----------------------------------------------------------------
     |  Model Events
     | -----------------------------------------------------------------
     */
    protected static function booted(): void
    {
        static::creating(function (self $barcode) {
            if (auth()->check()) {
                $barcode->created_by = $barcode->created_by ?? auth()->id();
                $barcode->updated_by = $barcode->updated_by ?? auth()->id();
            }
        });

        static::updating(function (self $barcode) {
            if (auth()->check()) {
                $barcode->updated_by = auth()->id();
            }
        });
    }

    /* -----------------------------------------------------------------
     |  Scopes
     | -----------------------------------------------------------------
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeOfFormat($query, string $format)
    {
        return $query->where('barcode_format', $format);
    }

    /**
     * Restrict barcodes to ones available on a given channel.
     * A barcode is "available" on a channel if EITHER it has no channel
     * assignments at all (= available everywhere) OR it has an explicit
     * assignment to that channel.
     */
    public function scopeForChannel($query, string $channelCode)
    {
        return $query->where(function ($q) use ($channelCode) {
            $q->whereDoesntHave('channels')
              ->orWhereHas('channels', function ($q2) use ($channelCode) {
                  $q2->where('code', $channelCode);
              });
        });
    }

    /* -----------------------------------------------------------------
     |  Relationships
     | -----------------------------------------------------------------
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(Channel::class, 'barcode_channel')
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /* -----------------------------------------------------------------
     |  Helpers
     | -----------------------------------------------------------------
     */
    public function isPrimary(): bool
    {
        return (bool) $this->is_primary === true;
    }

    public function primaryBadgeClass(): string
    {
        return $this->isPrimary() ? 'badge bg-primary' : 'badge bg-light text-muted';
    }

    /**
     * Convenience: returns true if barcode is restricted to specific channels.
     */
    public function isChannelRestricted(): bool
    {
        return $this->channels()->exists();
    }
}
