<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockAuditRequest;
use App\Models\Category;
use App\Models\Location;
use App\Models\StockAudit;
use App\Models\StockAuditItem;
use App\Models\StockAuditScan;
use App\Repositories\StockAuditRepository;
use App\Services\SettingService;
use App\Services\StockAuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

/**
 *   /stock-audits              — list
 *   /stock-audits/create       — start a new audit (pick a location)
 *   /stock-audits/{a}          — summary + missing-stock report
 *   /stock-audits/{a}/scan     — the scan screen (in-progress only)
 */
class StockAuditController extends Controller
{
    public function __construct(
        private StockAuditService    $service,
        private StockAuditRepository $repo,
        private SettingService       $settings,
    ) {}

    /* ─── List ─────────────────────────────────────────────── */

    public function index(): View
    {
        // Single aggregate query instead of separate COUNT() round-trips per card.
        $counts = StockAudit::selectRaw(
            "COUNT(*) as total,
             SUM(status = 'in_progress') as in_progress,
             SUM(status = 'completed') as completed,
             SUM(status = 'cancelled') as cancelled"
        )->first();

        $stats = [
            'audits_total'       => (int) $counts->total,
            'audits_in_progress' => (int) $counts->in_progress,
            'audits_completed'   => (int) $counts->completed,
            'audits_cancelled'   => (int) $counts->cancelled,
        ];

        return view('stock-audits.index', [
            'locations'  => Location::active()->orderBy('name')->get(['id', 'location_code', 'name']),
            'categories' => Category::active()->ordered()->get(['id', 'name']),
            'stats'      => $stats,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $q = $this->repo->query();

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        if ($locationId = $request->query('location_id')) {
            $q->where('location_id', $locationId);
        }
        if ($categoryId = $request->query('category_id')) {
            $q->where('category_id', $categoryId);
        }

        return DataTables::eloquent($q)
            ->addIndexColumn()
            ->editColumn('audit_number', fn (StockAudit $a) =>
                '<a href="' . route('stock-audits.show', $a) . '" class="link-reset"><code>' . e($a->audit_number) . '</code></a>'
            )
            ->editColumn('audit_date', fn (StockAudit $a) => optional($a->audit_date)->format('d M Y'))
            ->addColumn('location_label', fn (StockAudit $a) => $a->location ? e($a->location->name) : '—')
            ->addColumn('category_label', fn (StockAudit $a) => $a->category ? e($a->category->name) : 'All Stones')
            ->addColumn('progress_label', fn (StockAudit $a) =>
                '<span class="fw-semibold">' . (int) $a->matched_total . ' / ' . (int) $a->expected_total . '</span>'
                . ' <span class="text-muted fs-xxs">(' . $a->progressPercent() . '%)</span>'
            )
            ->addColumn('status_badge', function (StockAudit $a) {
                $class = match ($a->status) {
                    StockAudit::STATUS_IN_PROGRESS => 'status-pill status-in-progress',
                    StockAudit::STATUS_COMPLETED   => 'status-pill status-completed',
                    StockAudit::STATUS_CANCELLED   => 'status-pill status-cancelled',
                    default                          => 'status-pill',
                };
                return '<span class="' . $class . '"><span class="status-dot"></span>' . e($a->statusLabel()) . '</span>';
            })
            ->addColumn('actions', function (StockAudit $a) {
                $canScan = auth()->user()?->hasPermission('stock-audits.scan') ?? false;

                $html = '<div class="d-flex gap-1 justify-content-center">';
                $html .= '<a href="' . route('stock-audits.show', $a) . '" class="action-btn action-view" title="View"><i class="ti ti-eye"></i></a>';
                if ($a->isInProgress() && $canScan) {
                    $html .= '<a href="' . route('stock-audits.scan', $a) . '" class="action-btn action-scan" title="Continue Scanning"><i class="ti ti-scan"></i></a>';
                }
                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['audit_number', 'progress_label', 'status_badge', 'actions'])
            ->toJson();
    }

    /* ─── Create / Store ──────────────────────────────────── */

    public function create(): View
    {
        return view('stock-audits.create', [
            'locations'  => Location::active()->orderBy('name')->get(['id', 'location_code', 'name', 'type']),
            'categories' => Category::active()->ordered()->get(['id', 'name']),
        ]);
    }

    public function store(StoreStockAuditRequest $request): JsonResponse
    {
        try {
            $audit = $this->service->start($request->validated());

            return response()->json([
                'ok'       => true,
                'message'  => 'Audit started.',
                'redirect' => route('stock-audits.scan', $audit),
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ─── Show (summary + missing-stock report) ───────────── */

    public function show(StockAudit $stockAudit): View
    {
        $audit = $this->repo->find($stockAudit->id);

        return view('stock-audits.show', [
            'audit'       => $audit,
            'scanCounts'  => $this->service->scanResultCounts($audit),
            'canWriteOff' => $audit->isCompleted() && $audit->missingTotal() > 0,
        ]);
    }

    /* ─── Scan screen ──────────────────────────────────────── */

    public function scanScreen(StockAudit $stockAudit): View|RedirectResponse
    {
        if (! $stockAudit->isInProgress()) {
            return redirect()
                ->route('stock-audits.show', $stockAudit)
                ->with('info', 'This audit is already '.strtolower($stockAudit->statusLabel())." \u{2014} scanning is closed.");
        }

        $recentScans = StockAuditScan::where('stock_audit_id', $stockAudit->id)
            ->with(['item:id,lot_code,barcode,product_id', 'item.product:id,title,sku', 'scanner:id,name'])
            ->latest('scanned_at')
            ->latest('id')
            ->limit(25)
            ->get();

        return view('stock-audits.scan', [
            'audit'       => $this->repo->find($stockAudit->id),
            'recentScans' => $recentScans,
            'scanCounts'  => $this->service->scanResultCounts($stockAudit),
        ]);
    }

    public function scan(Request $request, StockAudit $stockAudit): JsonResponse
    {
        $value = trim((string) $request->input('value', ''));

        try {
            $scan = $this->service->scan($stockAudit, $value, auth()->id());
            $stockAudit->refresh();
            $scan->load(['item.product:id,title,sku']);

            $productTitle = $scan->item?->product?->title;

            return response()->json([
                'ok'      => true,
                'result'  => $scan->result,
                'message' => match ($scan->result) {
                    StockAuditScan::RESULT_MATCHED    => ($productTitle ?: $value) . ' — matched.',
                    StockAuditScan::RESULT_DUPLICATE  => "'{$value}' was already scanned in this audit.",
                    StockAuditScan::RESULT_UNEXPECTED => "'{$value}' isn't in this location's expected stock.",
                    default                            => '',
                },
                'scan' => [
                    'id'             => $scan->id,
                    'scanned_value'  => $scan->scanned_value,
                    'result'         => $scan->result,
                    'result_label'   => $scan->resultLabel(),
                    'badge_class'    => $scan->resultBadgeClass(),
                    'product_title'  => $productTitle,
                    'lot_code'       => $scan->item?->lot_code,
                    'scanned_at'     => $scan->scanned_at->format('H:i:s'),
                ],
                'progress' => [
                    'expected_total' => (int) $stockAudit->expected_total,
                    'matched_total'  => (int) $stockAudit->matched_total,
                    'missing_total'  => $stockAudit->missingTotal(),
                    'percent'        => $stockAudit->progressPercent(),
                ],
                'scan_counts' => $this->service->scanResultCounts($stockAudit),
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function undoScan(StockAudit $stockAudit): JsonResponse
    {
        try {
            $scan = $this->service->undoLastScan($stockAudit, (int) auth()->id());
            $stockAudit->refresh();

            return response()->json([
                'ok'       => true,
                'message'  => $scan ? "Undone: '{$scan->scanned_value}'." : 'Nothing to undo.',
                'scan_id'  => $scan?->id,
                'progress' => [
                    'expected_total' => (int) $stockAudit->expected_total,
                    'matched_total'  => (int) $stockAudit->matched_total,
                    'missing_total'  => $stockAudit->missingTotal(),
                    'percent'        => $stockAudit->progressPercent(),
                ],
                'scan_counts' => $this->service->scanResultCounts($stockAudit),
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ─── Status transitions ───────────────────────────────── */

    public function complete(StockAudit $stockAudit): JsonResponse
    {
        try {
            $audit = $this->service->complete($stockAudit);
            session()->flash('success', 'Audit completed successfully.');
            return response()->json([
                'ok'       => true,
                'message'  => 'Audit completed.',
                'redirect' => route('stock-audits.show', $audit),
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function cancel(StockAudit $stockAudit): JsonResponse
    {
        try {
            $audit = $this->service->cancel($stockAudit);
            session()->flash('info', 'Audit cancelled — no stock was adjusted.');
            return response()->json([
                'ok'       => true,
                'message'  => 'Audit cancelled.',
                'redirect' => route('stock-audits.show', $audit),
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function writeOffMissing(StockAudit $stockAudit): JsonResponse
    {
        try {
            $count = $this->service->writeOffMissing($stockAudit, (int) auth()->id());
            $message = $count > 0
                ? "{$count} missing piece(s) written off to the stock ledger."
                : 'Nothing left to write off.';
            session()->flash($count > 0 ? 'success' : 'info', $message);
            return response()->json([
                'ok'      => true,
                'message' => $message,
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ─── Missing-items table (server-side) ────────────────── */

    public function missingData(StockAudit $stockAudit): JsonResponse
    {
        $q = $this->service->missingItemsQuery($stockAudit);

        return DataTables::eloquent($q)
            ->addIndexColumn()
            ->addColumn('product_label', function (StockAuditItem $i) {
                $title = e($i->product?->title ?? '—');
                if ($i->isUntrackable()) {
                    return $title . ' <span class="badge badge-soft-secondary fs-xxs">no lot code</span>';
                }
                return $title;
            })
            ->addColumn('sku', fn (StockAuditItem $i) => $i->product?->sku ?? '—')
            ->addColumn('carat_weight', fn (StockAuditItem $i) => $i->purchaseProduct?->carat_weight ? $i->purchaseProduct->carat_weight . ' ct' : '—')
            ->addColumn('category', fn (StockAuditItem $i) => $i->product?->category?->name ?? '—')
            ->addColumn('lot_code_label', fn (StockAuditItem $i) => $i->lot_code ?: '—')
            ->addColumn('supplier', function (StockAuditItem $i) {
                $supplier = $i->purchaseProduct?->line?->purchase?->supplier;
                return $supplier ? ($supplier->company_name ?: $supplier->name) : '—';
            })
            ->addColumn('invoice_number', fn (StockAuditItem $i) => $i->purchaseProduct?->line?->purchase?->invoice_number ?? '—')
            ->addColumn('purchase_date', fn (StockAuditItem $i) =>
                $i->purchaseProduct?->line?->purchase?->purchase_date?->format('d M Y') ?? '—'
            )
            ->addColumn('cost_price', fn (StockAuditItem $i) => $this->settings->formatMoney((float) ($i->purchaseProduct?->price ?? 0)))
            ->addColumn('qty', fn () => 1)
            ->rawColumns(['product_label'])
            ->toJson();
    }

    /* ─── Exports ──────────────────────────────────────────── */

    public function exportExcel(StockAudit $stockAudit): StreamedResponse
    {
        $stockAudit->load('location');
        $items = $this->service->missingItemsQuery($stockAudit)->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Missing Stock');

        $sheet->fromArray(
            ['#', 'Lot Code', 'Product', 'SKU', 'Stone', 'Supplier', 'Invoice #', 'Purchase Date', 'Cost Price', 'Qty Missing'],
            null,
            'A1'
        );
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);

        $row = 2;
        foreach ($items as $index => $item) {
            $purchase = $item->purchaseProduct?->line?->purchase;
            $supplier = $purchase?->supplier;

            $sheet->fromArray([
                $index + 1,
                $item->lot_code ?: ($item->isUntrackable() ? 'No lot code' : '—'),
                $item->product?->title ?? '—',
                $item->product?->sku ?? '—',
                $item->product?->category?->name ?? '—',
                $supplier ? ($supplier->company_name ?: $supplier->name) : '—',
                $purchase?->invoice_number ?? '—',
                $purchase?->purchase_date?->format('d M Y') ?? '—',
                (float) ($item->purchaseProduct?->price ?? 0),
                1,
            ], null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = "{$stockAudit->audit_number}-missing-stock.xlsx";
        $tmpPath  = tempnam(sys_get_temp_dir(), 'audit') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tmpPath);

        return response()->streamDownload(function () use ($tmpPath) {
            readfile($tmpPath);
            @unlink($tmpPath);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf(StockAudit $stockAudit)
    {
        $stockAudit->load(['location', 'creator', 'category']);
        $items = $this->service->missingItemsQuery($stockAudit)->get();

        $pdf = Pdf::loadView('stock-audits.missing-report-pdf', [
            'audit'       => $stockAudit,
            'items'       => $items,
            'settings'    => $this->settings,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download("{$stockAudit->audit_number}-missing-stock.pdf");
    }
}
