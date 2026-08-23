<?php

namespace App\Repositories;

use App\Models\StockAudit;
use Illuminate\Database\Eloquent\Builder;

/**
 * Thin DAL for StockAudit. Mirrors StockTransferRepository.
 */
class StockAuditRepository
{
    public function query(): Builder
    {
        return StockAudit::query()->with([
            'location:id,location_code,name',
        ]);
    }

    public function find(int $id): ?StockAudit
    {
        return StockAudit::with([
            'location',
            'creator:id,name',
            'updater:id,name',
        ])->find($id);
    }

    public function refresh(StockAudit $audit): StockAudit
    {
        return $this->find($audit->id);
    }
}
