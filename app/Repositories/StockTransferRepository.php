<?php

namespace App\Repositories;

use App\Models\StockTransfer;
use Illuminate\Database\Eloquent\Builder;

/**
 * Thin DAL for StockTransfer. Mirrors PurchaseRepository / SaleRepository.
 */
class StockTransferRepository
{
    public function query(): Builder
    {
        return StockTransfer::query()
            ->with([
                'fromLocation:id,location_code,name',
                'toLocation:id,location_code,name',
            ])
            ->withCount('lines');
    }

    public function find(int $id): ?StockTransfer
    {
        return StockTransfer::with([
            'fromLocation',
            'toLocation',
            'lines.product:id,title,sku',
            'lines.purchaseProduct:id,purchase_line_id,barcode,lot_code,price,carat_weight',
            'lines.purchaseProduct.line:id,category_id,stone_type',
            'lines.purchaseProduct.line.category:id,name',
            'lines.toRack:id,code,name',
            'creator:id,name',
            'updater:id,name',
        ])->find($id);
    }

    public function refresh(StockTransfer $t): StockTransfer
    {
        return $this->find($t->id);
    }
}
