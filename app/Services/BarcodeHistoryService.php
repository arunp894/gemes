<?php

namespace App\Services;

use App\Models\Barcode;
use App\Models\PurchaseLine;
use App\Models\SaleLine;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/**
 * BarcodeHistoryService
 *
 * Aggregates the full lifecycle of a product identified by its barcode:
 * purchases, sales, stock movements, and a summarised KPI block.
 */
class BarcodeHistoryService
{
    /**
     * Look up a barcode value and return the complete product history.
     *
     * Returns ['found' => false, 'message' => '...'] when nothing matches.
     * Returns ['found' => true, ...data...] on success.
     */
    public function lookup(string $barcodeValue): array
    {
        // ── 1. Resolve barcode → product ─────────────────────────────────
        $barcode = Barcode::withTrashed()
            ->with([
                'product' => fn ($q) => $q->withTrashed()
                    ->with(['category.parent', 'media']),
            ])
            ->where('barcode_value', $barcodeValue)
            ->first();

        if (! $barcode || ! $barcode->product) {
            return [
                'found'   => false,
                'message' => "No product found for barcode: {$barcodeValue}",
            ];
        }

        $product   = $barcode->product;
        $productId = $product->id;

        // ── 2. Purchase lines ─────────────────────────────────────────────
        // purchase_lines has product_id directly, so no extra join needed.
        $purchaseLines = PurchaseLine::with(['purchase.supplier'])
            ->where('product_id', $productId)
            ->whereNull('purchase_lines.deleted_at')
            ->get()
            ->sortByDesc(fn ($l) => optional($l->purchase)->purchase_date)
            ->values();

        // Carat weight totals per line row (from purchase_products)
        $lineIds = $purchaseLines->pluck('id')->all();

        $caratsByLine = $lineIds
            ? DB::table('purchase_products')
                ->whereIn('purchase_line_id', $lineIds)
                ->whereNull('deleted_at')
                ->select('purchase_line_id', DB::raw('SUM(carat_weight) as total_carats'))
                ->groupBy('purchase_line_id')
                ->pluck('total_carats', 'purchase_line_id')
            : collect();

        // ── 3. Sale lines ─────────────────────────────────────────────────
        $saleLines = SaleLine::with(['sale.customer', 'sale.location', 'sale.channel'])
            ->where('product_id', $productId)
            ->whereNull('sale_lines.deleted_at')
            ->get()
            ->sortByDesc(fn ($l) => optional($l->sale)->sale_date)
            ->values();

        // ── 4. Stock movements ────────────────────────────────────────────
        $movements = StockMovement::with(['location'])
            ->where('product_id', $productId)
            ->whereNull('deleted_at')
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->get();

        // ── 5. KPI summary ────────────────────────────────────────────────
        $totalPurchasedQty    = (int) $purchaseLines->sum('total_qty');
        $totalPurchasedCarats = round((float) $caratsByLine->sum(), 3);
        $totalPurchasedValue  = round((float) $purchaseLines->sum('total'), 2);
        $purchaseCount        = $purchaseLines->count();

        $totalSoldQty   = (int) $saleLines->sum('qty');
        $totalSoldValue = round((float) $saleLines->sum('total'), 2);
        $saleCount      = $saleLines->count();

        $inQty  = (int) $movements->where('direction', StockMovement::DIRECTION_IN)->sum('qty');
        $outQty = (int) $movements->where('direction', StockMovement::DIRECTION_OUT)->sum('qty');
        $onHand = max(0, $inQty - $outQty);

        // ── 6. Category breadcrumb ────────────────────────────────────────
        $cat          = $product->category;
        $categoryPath = $cat
            ? ($cat->parent ? $cat->parent->name . ' › ' . $cat->name : $cat->name)
            : '—';

        // ── 7. Build response ─────────────────────────────────────────────
        return [
            'found'   => true,

            'barcode' => [
                'value'      => $barcode->barcode_value,
                'format'     => $barcode->barcode_format,
                'label'      => $barcode->barcode_label,
                'is_primary' => (bool) $barcode->is_primary,
            ],

            'product' => [
                'id'             => $product->id,
                'sku'            => $product->sku,
                'title'          => $product->title,
                'category_path'  => $categoryPath,
                'status'         => $product->statusLabel(),
                'status_class'   => $product->statusBadgeClass(),
                'is_gemstone'    => $product->isGemstone(),
                'carat_weight'   => $product->carat_weight,
                'stone_type'     => $product->stone_type,
                'colour_grade'   => $product->colour_grade,
                'clarity_grade'  => $product->clarity_grade,
                'cut_shape'      => $product->cut_shape,
                'treatment'      => $product->treatment,
                'certificate_no' => $product->certificate_number,
                'country_origin' => $product->country_of_origin,
                'website_enabled'=> (bool) $product->website_enabled,
                'thumb_url'      => $product->primary_thumb_url,
                'url'            => route('products.show', $product->id),
            ],

            'summary' => [
                'purchase_count'           => $purchaseCount,
                'total_purchased_qty'      => $totalPurchasedQty,
                'total_purchased_carats'   => $totalPurchasedCarats,
                'total_purchased_value'    => $totalPurchasedValue,
                'sale_count'               => $saleCount,
                'total_sold_qty'           => $totalSoldQty,
                'total_sold_value'         => $totalSoldValue,
                'on_hand_qty'              => $onHand,
                'in_qty'                   => $inQty,
                'out_qty'                  => $outQty,
            ],

            'purchases' => $purchaseLines->map(function ($line) use ($caratsByLine) {
                $p = $line->purchase;
                return [
                    'id'             => $p?->id,
                    'invoice_number' => $p?->invoice_number ?? '—',
                    'purchase_date'  => $p?->purchase_date?->format('d M Y') ?? '—',
                    'supplier'       => $p?->supplier?->name ?? '—',
                    'type'           => ucfirst($line->type),
                    'qty'            => (int) $line->total_qty,
                    'carats'         => round((float) ($caratsByLine[$line->id] ?? 0), 3),
                    'total'          => (float) $line->total,
                    'status'         => $p?->statusLabel() ?? '—',
                    'status_class'   => $p?->statusBadgeClass() ?? 'badge-soft-secondary',
                ];
            })->values()->all(),

            'sales' => $saleLines->map(function ($line) {
                $s = $line->sale;
                return [
                    'id'          => $s?->id,
                    'sale_number' => $s?->sale_number ?? '—',
                    'sale_date'   => $s?->sale_date?->format('d M Y') ?? '—',
                    'customer'    => $s?->customer?->name ?? 'Walk-in',
                    'location'    => $s?->location?->name ?? '—',
                    'channel'     => $s?->channel?->name ?? '—',
                    'qty'         => (int) $line->qty,
                    'unit_price'  => (float) $line->unit_price,
                    'total'       => (float) $line->total,
                    'status'      => $s?->statusLabel() ?? '—',
                    'status_class'=> $s?->statusBadgeClass() ?? 'badge-soft-secondary',
                ];
            })->values()->all(),

            'movements' => $movements->map(function ($m) {
                return [
                    'date'         => $m->movement_date?->format('d M Y') ?? '—',
                    'direction'    => strtoupper($m->direction),
                    'qty'          => (int) $m->qty,
                    'reason'       => $m->reasonLabel(),
                    'reason_class' => $m->reasonBadgeClass(),
                    'location'     => $m->location?->name ?? '—',
                    'notes'        => $m->notes ?? '',
                ];
            })->values()->all(),
        ];
    }
}
