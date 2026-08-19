<?php

namespace App\Repositories;

use App\Models\Purchase;
use Illuminate\Database\Eloquent\Builder;

/**
 * Thin data-access layer for the Purchase aggregate. Keeps the
 * controller and service layer free of query-builder noise.
 */
class PurchaseRepository
{
    /**
     * Base query with everything the index/show pages need.
     */
    public function query(): Builder
    {
        return Purchase::query()
            ->with([
                'supplier:id,supplier_code,name,company_name,invoice_prefix',
                'location:id,name,location_code,type',
            ]);
    }

    public function find(int $id): ?Purchase
    {
        return Purchase::with([
            'supplier',
            'location:id,name,location_code,type',
            // Historical lines still carry the old shared product via
            // lines.product; new-style lines carry it per-row instead —
            // each row is its own product now.
            'lines.product:id,title,sku,pack_type,outer_pack_name,outer_pack_contains,inner_pack_name,inner_pack_contains',
            'lines.category:id,name,is_gemstone',
            'lines.countryOfOrigin:id,name',
            'lines.rows.rack:id,code,name',
            'lines.rows.product:id,title,sku,status,website_enabled',
            'payments' => fn ($q) => $q->orderBy('payment_date')->orderBy('id'),
            'payments.creator:id,name',
        ])->find($id);
    }

    /**
     * Force a fresh hydration after a write. Used by the service so the
     * returned model has all relations populated for the API resource.
     */
    public function refresh(Purchase $p): Purchase
    {
        return $this->find($p->id);
    }
}
