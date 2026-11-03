<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Location;
use App\Models\Rental;
use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rent report: customer-wise summary plus one row per rental, kept apart from the
 * Sales Report. Rent income counts every rental that was not cancelled.
 */
class RentalReportController extends Controller
{
    private const ROW_LIMIT = 3000;

    public function index(Request $request, SettingService $settings): View
    {
        return view('reports.rentals', $this->report($request) + [
            'customers' => Customer::query()->orderBy('name')->get(['id', 'customer_code', 'name', 'company_name']),
            'locations' => Location::active()->ordered()->get(['id', 'name']),
            'settings'  => $settings,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $r = $this->report($request);

        $book = new Spreadsheet();
        $sum  = $book->getActiveSheet()->setTitle('By Customer');
        $sum->fromArray(['Customer', 'Rentals', 'Stones', 'Pcs', 'CT', 'Rent Income', 'Deposit Collected', 'Deposit Refunded', 'Kept (late/damage/lost)',
                         'Deposit Held', 'Overdue Rentals', 'Max Days Overdue'], null, 'A1');
        $row = 2;
        foreach ($r['byCustomer'] as $c) {
            $sum->fromArray([$c->name, $c->rentals, $c->stones, $c->pcs, round($c->carat, 3), round($c->rent, 2), round($c->deposit_paid, 2),
                             round($c->refunded, 2), round($c->kept, 2), round($c->held, 2), $c->overdue, $c->max_days], null, "A{$row}");
            $row++;
        }
        $t = $r['totals'];
        $sum->fromArray(['Total', $t['rentals'], $t['stones'], $t['pcs'], round($t['carat'], 3), round($t['rent'], 2), round($t['deposit_paid'], 2),
                         round($t['refunded'], 2), round($t['kept'], 2), round($t['held'], 2), $t['overdue'], ''], null, "A{$row}");
        $sum->getStyle("A{$row}:L{$row}")->getFont()->setBold(true);

        $det = $book->createSheet()->setTitle('Rentals');
        $det->fromArray(['Rent No', 'Customer', 'Location', 'From', 'To', 'Closed', 'Status', 'Stones', 'Pcs', 'CT', 'Rent Fee',
                         'Deposit Collected', 'Deposit Refunded', 'Kept', 'Deposit Held', 'Days Overdue', 'Lots'], null, 'A1');
        $row = 2;
        foreach ($r['rentals'] as $x) {
            $det->fromArray([$x->rent_number, $x->customer?->company_name ?: $x->customer?->name, $x->location?->name,
                $x->start_at->format('Y-m-d H:i'), $x->due_at->format('Y-m-d H:i'), $x->returned_at?->format('Y-m-d H:i'), $x->statusLabel(),
                $x->lines->count(), $x->lines->sum('qty'), round((float) $x->lines->sum('carat_weight'), 3), (float) $x->rent_total,
                (float) $x->deposit_paid, (float) $x->deposit_refunded, (float) $x->deduction_total, max(0, $x->depositHeld()), $x->overdueDays(),
                $x->lines->map(fn ($l) => $l->purchaseProduct?->lot_code)->filter()->implode(', ')], null, "A{$row}");
            $row++;
        }

        foreach ([$sum, $det] as $sheet) {
            $sheet->getStyle('1:1')->getFont()->setBold(true);
            for ($i = 1; $i <= Coordinate::columnIndexFromString($sheet->getHighestColumn()); $i++) {
                $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
            }
            $sheet->freezePane('A2');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'rentrep') . '.xlsx';
        (new Xlsx($book))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            readfile($tmp);
            @unlink($tmp);
        }, "rent-report_{$r['from']}_{$r['to']}.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function report(Request $request): array
    {
        $from = $request->query('from') ? Carbon::parse($request->query('from'))->toDateString() : now()->startOfMonth()->toDateString();
        $to   = $request->query('to')   ? Carbon::parse($request->query('to'))->toDateString()   : now()->toDateString();
        if ($to < $from) {
            [$from, $to] = [$to, $from];
        }
        $customerId = $request->integer('customer_id') ?: null;
        $locationId = $request->integer('location_id') ?: null;
        $status     = in_array($request->query('status'), ['active', 'overdue', 'returned', 'cancelled'], true) ? $request->query('status') : '';

        $q = Rental::query()
            ->with(['customer:id,customer_code,name,company_name', 'location:id,name',
                    'lines.purchaseProduct' => fn ($p) => $p->withTrashed()])
            ->whereBetween('start_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->when($status === 'overdue', fn ($q) => $q->overdue())
            ->when(in_array($status, ['active', 'returned', 'cancelled'], true), fn ($q) => $q->where('status', $status))
            ->orderByDesc('start_at');

        $rentals   = $q->limit(self::ROW_LIMIT + 1)->get();
        $truncated = $rentals->count() > self::ROW_LIMIT;
        $rentals   = $rentals->take(self::ROW_LIMIT);

        $blank  = fn () => ['rentals' => 0, 'stones' => 0, 'pcs' => 0, 'carat' => 0.0, 'rent' => 0.0, 'deposit_paid' => 0.0,
                            'refunded' => 0.0, 'kept' => 0.0, 'held' => 0.0, 'overdue' => 0];
        $totals = $blank();
        $by     = [];

        foreach ($rentals as $r) {
            $cancelled = $r->isCancelled();
            $key = $r->customer_id;
            $by[$key] ??= ($blank() + ['name' => $r->customer?->company_name ?: ($r->customer?->name ?? '—'), 'max_days' => 0]);
            $add = [
                'rentals'      => 1,
                'stones'       => $r->lines->count(),
                'pcs'          => (int) $r->lines->sum('qty'),
                'carat'        => (float) $r->lines->sum('carat_weight'),
                'rent'         => $cancelled ? 0.0 : (float) $r->rent_total,
                'deposit_paid' => $cancelled ? 0.0 : (float) $r->deposit_paid,
                'refunded'     => (float) $r->deposit_refunded,
                'kept'         => (float) $r->deduction_total,
                'held'         => $r->isActive() || $r->isReturned() ? max(0, $r->depositHeld()) : 0.0,
                'overdue'      => $r->isOverdue() ? 1 : 0,
            ];
            foreach ($add as $k => $v) {
                $by[$key][$k]  += $v;
                $totals[$k]    += $v;
            }
            $by[$key]['max_days'] = max($by[$key]['max_days'], $r->overdueDays());
        }

        $byCustomer = collect($by)->map(fn ($c) => (object) $c)->sortByDesc('rent')->values();

        return [
            'rentals'    => $rentals,
            'byCustomer' => $byCustomer,
            'totals'     => $totals,
            'truncated'  => $truncated,
            'rowLimit'   => self::ROW_LIMIT,
            'from'       => $from,
            'to'         => $to,
            'customerId' => $customerId,
            'locationId' => $locationId,
            'status'     => $status,
        ];
    }
}
