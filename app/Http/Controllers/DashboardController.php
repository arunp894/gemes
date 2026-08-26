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
        $user = auth()->user();

        // Role/permission gates — mirror the exact permission slugs already
        // used to gate each module elsewhere in the app (@permission(...) on
        // their own index pages), so a user only ever sees dashboard data
        // for modules they're actually allowed into. Super-admins (is_super)
        // bypass every check here too, same as hasPermission() does app-wide.
        $canSales     = $user->hasPermission('sales.view');
        $canPurchases = $user->hasPermission('purchases.view');
        $canProducts  = $user->hasPermission('products.view');
        $canCustomers = $user->hasPermission('customers.view');
        $canSuppliers = $user->hasPermission('suppliers.view');
        $canStock     = $user->hasPermission('stock.view');

        $today     = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        /* ── KPI Counts (only for permitted modules) ────────────────── */
        $totalProducts = $activeProducts = 0;
        if ($canProducts) {
            $productCounts  = Product::selectRaw('COUNT(*) as total, SUM(status = 1) as active')->first();
            $totalProducts  = (int) $productCounts->total;
            $activeProducts = (int) $productCounts->active;
        }

        $totalSuppliers = $activeSuppliers = 0;
        if ($canSuppliers) {
            $supplierCounts  = Supplier::selectRaw('COUNT(*) as total, SUM(status = 1) as active')->first();
            $totalSuppliers  = (int) $supplierCounts->total;
            $activeSuppliers = (int) $supplierCounts->active;
        }

        $totalCustomers = $activeCustomers = 0;
        if ($canCustomers) {
            $customerCounts  = Customer::selectRaw('COUNT(*) as total, SUM(status = 1) as active')->first();
            $totalCustomers  = (int) $customerCounts->total;
            $activeCustomers = (int) $customerCounts->active;
        }

        /* ── Sales KPIs ────────────────────────────────────────────── */
        $salesThisMonth = (object) ['count' => 0, 'revenue' => 0];
        $salesRevenueChange = 0;
        if ($canSales) {
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
        }

        /* ── Purchase KPIs ─────────────────────────────────────────── */
        $purchasesThisMonth = (object) ['count' => 0, 'spend' => 0];
        $purchaseSpendChange = 0;
        if ($canPurchases) {
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
        }

        /* ── 12-Month Trend (Sales vs Purchases) ───────────────────── */
        $months       = [];
        $salesData    = [];
        $purchaseData = [];

        if ($canSales || $canPurchases) {
            for ($i = 11; $i >= 0; $i--) {
                $m     = Carbon::now()->subMonths($i);
                $start = $m->copy()->startOfMonth();
                $end   = $m->copy()->endOfMonth();

                $months[] = $m->format('M y');

                $salesData[] = $canSales
                    ? (float) Sale::whereIn('status', [Sale::STATUS_POSTED, Sale::STATUS_COMPLETED])
                        ->whereBetween('sale_date', [$start, $end])
                        ->sum('grand_total')
                    : 0;

                $purchaseData[] = $canPurchases
                    ? (float) Purchase::where('status', Purchase::STATUS_POSTED)
                        ->whereBetween('purchase_date', [$start, $end])
                        ->sum('grand_total')
                    : 0;
            }
        }

        /* ── Recent Sales (last 8) ─────────────────────────────────── */
        $recentSales = collect();
        if ($canSales) {
            $recentSales = Sale::with(['customer', 'location'])
                ->whereIn('status', [Sale::STATUS_POSTED, Sale::STATUS_COMPLETED, Sale::STATUS_DRAFT])
                ->orderByDesc('sale_date')
                ->orderByDesc('id')
                ->limit(8)
                ->get();
        }

        /* ── Recent Purchases (last 5) ─────────────────────────────── */
        $recentPurchases = collect();
        if ($canPurchases) {
            $recentPurchases = Purchase::with('supplier')
                ->whereIn('status', [Purchase::STATUS_POSTED, Purchase::STATUS_DRAFT])
                ->orderByDesc('purchase_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get();
        }

        /* ── Today's quick counts ──────────────────────────────────── */
        $todaySalesCount    = $canSales ? Sale::whereDate('sale_date', $today)->count() : 0;
        $todayPurchaseCount = $canPurchases ? Purchase::whereDate('purchase_date', $today)->count() : 0;

        /* ── Stock summary: distinct products with at least 1 purchase_product ─
         *  purchase_products has no product_id; it links via purchase_line_id
         *  → purchase_lines.product_id.                                         */
        $inStockCount = 0;
        if ($canStock) {
            $inStockCount = DB::table('purchase_products as pp')
                ->join('purchase_lines as pl', 'pl.id', '=', 'pp.purchase_line_id')
                ->whereNull('pp.deleted_at')
                ->whereNull('pl.deleted_at')
                ->distinct()
                ->count('pl.product_id');
        }

        return view('welcome', compact(
            'canSales',
            'canPurchases',
            'canProducts',
            'canCustomers',
            'canSuppliers',
            'canStock',
            'totalProducts',
            'activeProducts',
            'totalSuppliers',
            'activeSuppliers',
            'totalCustomers',
            'activeCustomers',
            'salesThisMonth',
            'salesRevenueChange',
            'purchasesThisMonth',
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
