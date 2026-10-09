<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Location;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sales report: one row per sold item (sale line), tied back to the purchase
 * line (lot) it came from, with cost, sale and profit per row plus running totals.
 */
class SalesReportController extends Controller
{
    private const ROW_LIMIT = 3000;

    public function index(Request $request, SettingService $settings): View
    {
        return view('reports.sales', $this->report($request) + [
            'customers' => Customer::query()->orderBy('name')->get(['id', 'customer_code', 'name', 'company_name']),
            'locations' => Location::active()->ordered()->get(['id', 'name']),
            'settings'  => $settings,
        ]);
    }

    /** Same rows and filters as the page, as an .xlsx download. */
    public function export(Request $request): StreamedResponse
    {
        $r = $this->report($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sales Report');

        $headers = ['Date', 'Customer', 'Stone', 'Lot No', 'INV No', 'Carat', 'Pcs', 'Cost / CT', 'Sold / CT', 'Total Sale', 'Total Cost',
                    'Profit', 'Total Sale (Σ)', 'Total Profit (Σ)', 'Location', 'Purchase Inv', 'Supplier'];
        $sheet->fromArray($headers, null, 'A1');
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);

        $row = 2;
        foreach ($r['rows'] as $x) {
            $sheet->fromArray([
                optional($x->date)->format('Y-m-d'),
                $x->customer,
                $x->stone,
                $x->lot,
                $x->invoice,
                $x->carat,
                $x->pcs,
                $x->cost_per_ct,
                $x->sold_per_ct,
                round($x->total_sale, 2),
                round($x->total_cost, 2),
                round($x->profit, 2),
                round($x->run_sale, 2),
                round($x->run_profit, 2),
                $x->sale->location?->name,
                $x->purchase?->invoice_number,
                $x->supplier,
            ], null, "A{$row}");
            $row++;
        }

        if ($r['rows']->isNotEmpty()) {
            $t = $r['totals'];
            $sheet->fromArray(['', '', '', '', 'Total', $t['carat'], $t['pcs'], '', '', round($t['sale'], 2), round($t['cost'], 2), round($t['profit'], 2)], null, "A{$row}");
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true);
        }

        $sheet->getStyle("F2:F{$row}")->getNumberFormat()->setFormatCode('0.000');
        $sheet->getStyle("H2:N{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        for ($i = 1; $i <= count($headers); $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
        $sheet->freezePane('A2');

        $tmpPath = tempnam(sys_get_temp_dir(), 'salesrep') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tmpPath);

        return response()->streamDownload(function () use ($tmpPath) {
            readfile($tmpPath);
            @unlink($tmpPath);
        }, "sales-report_{$r['from']}_{$r['to']}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** @return array{rows: \Illuminate\Support\Collection, totals: array, truncated: bool, rowLimit: int, from: string, to: string, customerId: ?int, locationId: ?int, search: string} */
    private function report(Request $request): array
    {
        $from = $request->query('from') ? Carbon::parse($request->query('from'))->toDateString() : now()->startOfMonth()->toDateString();
        $to   = $request->query('to')   ? Carbon::parse($request->query('to'))->toDateString()   : now()->toDateString();
        if ($to < $from) {
            [$from, $to] = [$to, $from];
        }
        $customerId = $request->integer('customer_id') ?: null;
        $locationId = $request->integer('location_id') ?: null;
        $search     = trim((string) $request->query('q', ''));
        $like       = '%' . addcslashes($search, '%_\\') . '%';

        $lines = SaleLine::query()
            ->whereHas('sale', function ($q) use ($from, $to, $customerId, $locationId) {
                $q->whereIn('status', [Sale::STATUS_POSTED, Sale::STATUS_COMPLETED])
                    ->whereBetween('sale_date', [$from, $to])
                    ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
                    ->when($locationId, fn ($q) => $q->where('location_id', $locationId));
            })
            // Search box: lot number or customer name, plain SQL LIKE.
            ->when($search !== '', function ($q) use ($like) {
                $q->where(function ($q) use ($like) {
                    $q->whereHas('purchaseProduct', fn ($p) => $p->withTrashed()->where('lot_code', 'like', $like))
                        ->orWhereHas('product.purchaseProduct', fn ($p) => $p->where('lot_code', 'like', $like))
                        ->orWhereHas('sale.customer', fn ($c) => $c->where(
                            fn ($c) => $c->where('name', 'like', $like)->orWhere('company_name', 'like', $like)
                        ));
                });
            })
            ->with([
                'sale:id,sale_number,sale_date,status,payment_status,customer_id,location_id,channel_id',
                'sale.customer:id,customer_code,name,company_name',
                'sale.location:id,name',
                'sale.channel:id,name',
                'product:id,title,sku,category_id',
                'product.category:id,name',
                'product.purchaseProduct:id,product_id,lot_code,price,carat_weight',
                'purchaseProduct' => fn ($q) => $q->withTrashed(),
                'purchaseProduct.line' => fn ($q) => $q->withTrashed(),
                'purchaseProduct.line.purchase' => fn ($q) => $q->withTrashed(),
                'purchaseProduct.line.purchase.supplier:id,name,company_name',
            ])
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->orderBy('sales.sale_date')->orderBy('sale_lines.id')
            ->select('sale_lines.*')
            ->limit(self::ROW_LIMIT + 1)
            ->get();

        $truncated = $lines->count() > self::ROW_LIMIT;
        $lines     = $lines->take(self::ROW_LIMIT);

        $runSale = $runProfit = 0.0;
        $totals  = ['carat' => 0.0, 'pcs' => 0, 'sale' => 0.0, 'cost' => 0.0, 'profit' => 0.0];

        $rows = $lines->map(function (SaleLine $l) use (&$runSale, &$runProfit, &$totals) {
            // A line sold by search has no piece attached — fall back to the product's own piece.
            $pp       = $l->purchaseProduct ?? $l->product?->purchaseProduct;
            $weighed  = $pp && $pp->carat_weight !== null && (float) $l->carat_weight > 0;
            $carat    = $weighed ? (float) $l->carat_weight : null;
            $qty      = (int) $l->qty;

            $rate     = (float) $l->cost_price;
            if ($rate <= 0 && $pp && ! $l->purchase_product_id) {
                $rate = (float) $pp->price;
            }

            $gross    = (float) $l->subtotal;
            $discount = (float) $l->discount_amount;
            $sale     = $gross - $discount;                                  // net of discount, before tax
            $cost     = $weighed ? $rate * $carat : $rate * $qty;
            $profit   = $sale - $cost;

            $runSale   += $sale;
            $runProfit += $profit;
            $totals['carat']  += (float) $carat;
            $totals['pcs']    += $qty;
            $totals['sale']   += $sale;
            $totals['cost']   += $cost;
            $totals['profit'] += $profit;

            $purchase = $pp?->line?->purchase;

            return (object) [
                'date'        => $l->sale->sale_date,
                'name'        => $l->product?->title ?? '—',
                'stone'       => $l->product?->category?->name ?? ($l->product?->title ?? '—'),
                'customer'    => $l->sale->customer?->company_name ?: ($l->sale->customer?->name ?? '—'),
                'sku'         => $l->product?->sku,
                'lot'         => $pp?->lot_code,
                'invoice'     => $l->sale->sale_number,
                'sale'        => $l->sale,
                'carat'       => $carat,
                'pcs'         => $qty,
                'cost_per_ct' => $weighed ? $rate : null,
                'rate'        => $rate,
                'sold_per_ct' => $weighed && $carat > 0 ? $sale / $carat : null,
                'total_sale'  => $sale,
                'total_cost'  => $cost,
                'profit'      => $profit,
                'margin'      => $sale > 0 ? $profit / $sale * 100 : null,
                'run_sale'    => $runSale,
                'run_profit'  => $runProfit,
                'gross'       => $gross,
                'discount'    => $discount,
                'tax'         => (float) $l->tax_amount,
                'weighed'     => $weighed,
                'purchase'    => $purchase,
                'supplier'    => $purchase?->supplier?->company_name ?: $purchase?->supplier?->name,
            ];
        });

        return [
            'rows'       => $rows,
            'totals'     => $totals,
            'truncated'  => $truncated,
            'rowLimit'   => self::ROW_LIMIT,
            'from'       => $from,
            'to'         => $to,
            'customerId' => $customerId,
            'locationId' => $locationId,
            'search'     => $search,
        ];
    }
}
