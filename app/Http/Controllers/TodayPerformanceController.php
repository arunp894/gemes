<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\SalePayment;
use App\Models\StockAudit;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TodayPerformanceController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $today = Carbon::today();

        // Mirror the exact permission slugs the Dashboard already gates
        // each module's data behind, so this page never leaks data a
        // user isn't otherwise allowed to see.
        $canSales       = $user->hasPermission('sales.view');
        $canPurchases   = $user->hasPermission('purchases.view');
        $canStock       = $user->hasPermission('stock.view');
        $canCustomers   = $user->hasPermission('customers.view');
        $canStockAudits = $user->hasPermission('stock-audits.view');

        /* ── KPIs ─────────────────────────────────────────────── */
        $salesToday = (object) ['count' => 0, 'revenue' => 0];
        if ($canSales) {
            $salesToday = Sale::whereIn('status', [Sale::STATUS_POSTED, Sale::STATUS_COMPLETED])
                ->whereDate('sale_date', $today)
                ->selectRaw('COUNT(*) as count, COALESCE(SUM(grand_total),0) as revenue')
                ->first();
        }

        $purchasesToday = (object) ['count' => 0, 'spend' => 0];
        if ($canPurchases) {
            $purchasesToday = Purchase::where('status', Purchase::STATUS_POSTED)
                ->whereDate('purchase_date', $today)
                ->selectRaw('COUNT(*) as count, COALESCE(SUM(grand_total),0) as spend')
                ->first();
        }

        $stockInQty = $stockOutQty = 0;
        if ($canStock) {
            $movementTotals = StockMovement::whereDate('movement_date', $today)
                ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN qty ELSE 0 END),0) as in_qty")
                ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'out' THEN qty ELSE 0 END),0) as out_qty")
                ->first();
            $stockInQty  = (int) $movementTotals->in_qty;
            $stockOutQty = (int) $movementTotals->out_qty;
        }

        $newCustomersToday = 0;
        if ($canCustomers) {
            $newCustomersToday = Customer::whereDate('created_at', $today)->count();
        }

        /* ── Hourly Sales vs Purchases (today only) ─────────────── */
        $hours = range(0, 23);
        $salesByHour = $purchasesByHour = array_fill(0, 24, 0.0);

        if ($canSales) {
            Sale::whereIn('status', [Sale::STATUS_POSTED, Sale::STATUS_COMPLETED])
                ->whereDate('sale_date', $today)
                ->selectRaw('HOUR(created_at) as h, COALESCE(SUM(grand_total),0) as total')
                ->groupBy('h')
                ->get()
                ->each(function ($row) use (&$salesByHour) {
                    $salesByHour[(int) $row->h] = (float) $row->total;
                });
        }
        if ($canPurchases) {
            Purchase::where('status', Purchase::STATUS_POSTED)
                ->whereDate('purchase_date', $today)
                ->selectRaw('HOUR(created_at) as h, COALESCE(SUM(grand_total),0) as total')
                ->groupBy('h')
                ->get()
                ->each(function ($row) use (&$purchasesByHour) {
                    $purchasesByHour[(int) $row->h] = (float) $row->total;
                });
        }

        /* ── Payment methods collected today ────────────────────── */
        $paymentMethodLabels = $paymentMethodTotals = [];
        if ($canSales) {
            SalePayment::whereDate('payment_date', $today)
                ->selectRaw('payment_method, COALESCE(SUM(amount),0) as total')
                ->groupBy('payment_method')
                ->having('total', '>', 0)
                ->get()
                ->each(function ($row) use (&$paymentMethodLabels, &$paymentMethodTotals) {
                    $paymentMethodLabels[] = ucfirst(str_replace('_', ' ', $row->payment_method));
                    $paymentMethodTotals[] = (float) $row->total;
                });
        }

        /* ── Top products sold today ─────────────────────────────── */
        $topProductsToday = collect();
        if ($canSales) {
            $topProductsToday = SaleLine::query()
                ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
                ->join('products', 'products.id', '=', 'sale_lines.product_id')
                ->whereIn('sales.status', [Sale::STATUS_POSTED, Sale::STATUS_COMPLETED])
                ->whereDate('sales.sale_date', $today)
                ->whereNull('sale_lines.deleted_at')
                ->groupBy('products.id', 'products.title', 'products.sku')
                ->selectRaw('products.id, products.title, products.sku, SUM(sale_lines.qty) as qty_sold, SUM(sale_lines.total) as revenue')
                ->orderByDesc('revenue')
                ->limit(5)
                ->get();
        }

        /* ── Recent activity feed (sales + purchases + audits, today) ── */
        $activity = collect();

        if ($canSales) {
            Sale::with('customer:id,name')
                ->whereIn('status', [Sale::STATUS_POSTED, Sale::STATUS_COMPLETED, Sale::STATUS_DRAFT])
                ->whereDate('sale_date', $today)
                ->latest('created_at')
                ->limit(8)
                ->get()
                ->each(function (Sale $sale) use ($activity) {
                    $activity->push([
                        'type'  => 'sale',
                        'icon'  => 'ti-receipt',
                        'color' => 'success',
                        'title' => $sale->sale_number,
                        'meta'  => $sale->customer?->name ?? 'Walk-in',
                        'amount' => (float) $sale->grand_total,
                        'time'  => $sale->created_at,
                        'url'   => route('sales.show', $sale),
                    ]);
                });
        }

        if ($canPurchases) {
            Purchase::with('supplier:id,name,company_name')
                ->whereIn('status', [Purchase::STATUS_POSTED, Purchase::STATUS_DRAFT])
                ->whereDate('purchase_date', $today)
                ->latest('created_at')
                ->limit(8)
                ->get()
                ->each(function (Purchase $purchase) use ($activity) {
                    $activity->push([
                        'type'  => 'purchase',
                        'icon'  => 'ti-shopping-cart',
                        'color' => 'primary',
                        'title' => $purchase->invoice_number,
                        'meta'  => $purchase->supplier?->company_name ?: $purchase->supplier?->name,
                        'amount' => (float) $purchase->grand_total,
                        'time'  => $purchase->created_at,
                        'url'   => route('purchases.show', $purchase),
                    ]);
                });
        }

        if ($canStockAudits) {
            StockAudit::with('location:id,name')
                ->where(function ($q) use ($today) {
                    $q->whereDate('started_at', $today)->orWhereDate('completed_at', $today);
                })
                ->latest('started_at')
                ->limit(6)
                ->get()
                ->each(function (StockAudit $audit) use ($activity) {
                    $activity->push([
                        'type'  => 'audit',
                        'icon'  => 'ti-clipboard-list',
                        'color' => $audit->isCompleted() ? 'info' : 'warning',
                        'title' => $audit->audit_number,
                        'meta'  => ($audit->isCompleted() ? 'Completed at ' : 'Started at ') . ($audit->location?->name ?? '—'),
                        'amount' => null,
                        'time'  => $audit->isCompleted() ? $audit->completed_at : $audit->started_at,
                        'url'   => route('stock-audits.show', $audit),
                    ]);
                });
        }

        $activity = $activity->filter(fn ($row) => $row['time'] !== null)
            ->sortByDesc('time')
            ->take(12)
            ->values();

        return view('reports.today-performance', compact(
            'canSales', 'canPurchases', 'canStock', 'canCustomers', 'canStockAudits',
            'salesToday', 'purchasesToday', 'stockInQty', 'stockOutQty', 'newCustomersToday',
            'hours', 'salesByHour', 'purchasesByHour',
            'paymentMethodLabels', 'paymentMethodTotals',
            'topProductsToday', 'activity',
        ));
    }
}
