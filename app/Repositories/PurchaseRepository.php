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
            ->with(['supplier:id,supplier_code,name,company_name,invoice_prefix']);
    }

    public function find(int $id): ?Purchase
    {
        return Purchase::with([
            'supplier',
            'lines.product:id,title,sku,pack_type,outer_pack_name,outer_pack_contains,inner_pack_name,inner_pack_contains',
            'lines.rows.rack:id,code,name',
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
