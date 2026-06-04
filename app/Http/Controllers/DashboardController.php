<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today     = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        /* ── KPI Counts ────────────────────────────────────────────── */
        $totalProducts  = Product::count();
        $activeProducts = Product::active()->count();

        $totalSuppliers  = Supplier::count();
        $activeSuppliers = Supplier::active()->count();

        $totalCustomers  = Customer::count();
        $activeCustomers = Customer::active()->count();

        /* ── Sales KPIs ────────────────────────────────────────────── */
        $salesThisMonth = Sale::whereIn('status', [Sale::STATUS_POSTED, Sale::STATUS_COMPLETED])
            ->where('sale_date', '>=', $thisMonth)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(grand_total),0) as revenue')
            ->first();

        $salesLastMonth = Sale::whereIn('status', [Sale::STATUS_POSTED, Sale::STATUS_COMPLETED])
            ->whereBetween('sale_date', [$lastMonth, $lastMonthEnd])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(grand_total),0) as revenue')
            ->first();

        $salesRevenueChange = $salesLastMonth->revenue > 0
            ? round((($salesThisMonth->revenue - $salesLastMonth->revenue) / $salesLastMonth->revenue) * 100, 1)
            : 0;

        /* ── Purchase KPIs ─────────────────────────────────────────── */
        $purchasesThisMonth = Purchase::where('status', Purchase::STATUS_POSTED)
            ->where('purchase_date', '>=', $thisMonth)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(grand_total),0) as spend')
            ->first();

        $purchasesLastMonth = Purchase::where('status', Purchase::STATUS_POSTED)
            ->whereBetween('purchase_date', [$lastMonth, $lastMonthEnd])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(grand_total),0) as spend')
            ->first();

        $purchaseSpendChange = $purchasesLastMonth->spend > 0
            ? round((($purchasesThisMonth->spend - $purchasesLastMonth->spend) / $purchasesLastMonth->spend) * 100, 1)
            : 0;

        /* ── 12-Month Trend (Sales vs Purchases) ───────────────────── */
        $months      = [];
        $salesData   = [];
        $purchaseData = [];

        for ($i = 11; $i >= 0; $i--) {
            $m     = Carbon::now()->subMonths($i);
            $start = $m->copy()->startOfMonth();
            $end   = $m->copy()->endOfMonth();

            $months[] = $m->format('M y');

            $salesData[] = (float) Sale::whereIn('status', [Sale::STATUS_POSTED, Sale::STATUS_COMPLETED])
                ->whereBetween('sale_date', [$start, $end])
                ->sum('grand_total');

            $purchaseData[] = (float) Purchase::where('status', Purchase::STATUS_POSTED)
                ->whereBetween('purchase_date', [$start, $end])
                ->sum('grand_total');
        }

        /* ── Recent Sales (last 8) ─────────────────────────────────── */
        $recentSales = Sale::with(['customer', 'location'])
            ->whereIn('status', [Sale::STATUS_POSTED, Sale::STATUS_COMPLETED, Sale::STATUS_DRAFT])
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        /* ── Recent Purchases (last 5) ─────────────────────────────── */
        $recentPurchases = Purchase::with('supplier')
            ->whereIn('status', [Purchase::STATUS_POSTED, Purchase::STATUS_DRAFT])
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        /* ── Today's quick counts ──────────────────────────────────── */
        $todaySalesCount    = Sale::whereDate('sale_date', $today)->count();
        $todayPurchaseCount = Purchase::whereDate('purchase_date', $today)->count();

        /* ── Stock summary: distinct products with at least 1 purchase_product ─
         *  purchase_products has no product_id; it links via purchase_line_id
         *  → purchase_lines.product_id.                                         */
        $inStockCount = DB::table('purchase_products as pp')
            ->join('purchase_lines as pl', 'pl.id', '=', 'pp.purchase_line_id')
            ->whereNull('pp.deleted_at')
            ->whereNull('pl.deleted_at')
            ->distinct()
            ->count('pl.product_id');

        return view('welcome', compact(
            'totalProducts',
            'activeProducts',
            'totalSuppliers',
            'activeSuppliers',
            'totalCustomers',
            'activeCustomers',
            'salesThisMonth',
            'salesLastMonth',
            'salesRevenueChange',
            'purchasesThisMonth',
            'purchasesLastMonth',
            'purchaseSpendChange',
            'months',
            'salesData',
            'purchaseData',
            'recentSales',
            'recentPurchases',
            'todaySalesCount',
            'todayPurchaseCount',
            'inStockCount',
        ));
    }
}
