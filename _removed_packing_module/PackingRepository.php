<?php

namespace App\Repositories;

use App\Models\Packing;
use Illuminate\Database\Eloquent\Builder;

class PackingRepository
{
    public function query(): Builder
    {
        return Packing::query()->with(['location']);
    }

    public function find(int $id): ?Packing
    {
        return Packing::with([
            'location',
            'sources.purchaseProduct.line.category',
            'sources.purchaseProduct.line.purchase.supplier',
            'outputs.product.category',
            'outputs.rack',
            'creator',
            'updater',
        ])->find($id);
    }

    public function refresh(Packing $packing): Packing
    {
        return $this->find($packing->id) ?? $packing->fresh();
    }
}
