<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A snapshot of the customer + cart behind a PayPal order at creation time.
 * See the create_paypal_orders_table migration for why this exists.
 */
class PaypalOrder extends Model
{
    protected $fillable = [
        'paypal_order_id',
        'customer_id',
        'cart_snapshot',
    ];

    protected $casts = [
        'cart_snapshot' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
