<?php

namespace App\Repositories;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;

/**
 * Thin data-access layer for Sale. Keeps query noise out of the
 * controller and service. Mirrors PurchaseRepository.
 */
class SaleRepository
{
    /**
     * Base query for index/listing — only the slim fields the table
     * needs, eager-loaded for the DataTables JSON renderer.
     */
    public function query(): Builder
    {
        return Sale::query()->with([
            'customer:id,customer_code,name,company_name,phone',
            'location:id,location_code,name',
            'channel:id,name,code',
            'salesperson:id,name',
        ]);
    }

    /**
     * Full hydration for show/edit pages.
     */
    public function find(int $id): ?Sale
    {
        return Sale::with([
            'customer',
            'location',
            'channel',
            'salesperson:id,name,email',
            'lines.product:id,title,sku',
            'lines.purchaseProduct:id,purchase_line_id,barcode,price',
            'payments' => fn ($q) => $q->orderBy('payment_date')->orderBy('id'),
            'payments.creator:id,name',
            'creator:id,name',
            'updater:id,name',
        ])->find($id);
    }

    /**
     * Force a fresh hydration after a write so the API response carries
     * computed totals + relations.
     */
    public function refresh(Sale $sale): Sale
    {
        return $this->find($sale->id);
    }
}
