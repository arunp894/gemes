<?php

namespace Tests\Feature\StockAudits;

use App\Models\Category;
use App\Models\Location;
use App\Models\Purchase;
use App\Models\PurchaseProduct;
use App\Models\StockAudit;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

/**
 * End-to-end coverage of the Stock Audit workflow through real HTTP
 * routes: start an audit, scan matched/duplicate/unexpected items, undo
 * a scan, complete/cancel, write off missing stock, and export reports —
 * plus the permission gate on every step and the "scan screen redirects
 * once the audit is no longer in progress" behaviour.
 */
class StockAuditLifecycleTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPermissions;

    /**
     * Create a posted purchase (via the real PurchaseService, so it writes
     * genuine stock_movements) with $count single-piece lines at $location,
     * and return the resulting Purchase with its inventory rows loaded.
     */
    private function purchaseStock(Location $location, Category $category, Supplier $supplier, int $count): Purchase
    {
        $lines = [];
        for ($i = 0; $i < $count; $i++) {
            $lines[] = [
                'category_id'  => $category->id,
                'title'        => "Test Stone {$i}",
                'type'         => 'piece',
                'package_qty'  => 1,
                'rows'         => [[
                    'qty'          => 1,
                    'carat_weight' => 1.5,
                    'price'        => 1000,
                ]],
            ];
        }

        $purchase = app(PurchaseService::class)->create([
            'supplier_id'   => $supplier->id,
            'location_id'   => $location->id,
            'purchase_date' => now()->toDateString(),
            'tax_type'      => 'none',
            'status'        => 'posted',
            'lines'         => $lines,
        ]);

        return $purchase->load('lines.rows');
    }

    private function lotCodes(Purchase $purchase): array
    {
        return $purchase->inventoryRows->pluck('lot_code')->all();
    }

    public function test_full_audit_lifecycle_scan_undo_complete_and_export(): void
    {
        $location = Location::factory()->create();
        $category = Category::factory()->create();
        $supplier = Supplier::factory()->create();
        $purchase = $this->purchaseStock($location, $category, $supplier, 2);
        [$lotA, $lotB] = $this->lotCodes($purchase);

        $this->actingAsUserWithPermissions([
            'stock-audits.view', 'stock-audits.create', 'stock-audits.scan',
            'stock-audits.complete', 'stock-audits.write-off',
        ]);

        // ── Start the audit ──────────────────────────────────────
        $startResponse = $this->postJson(route('stock-audits.store'), [
            'location_id' => $location->id,
            'category_id' => $category->id,
        ]);
        $startResponse->assertCreated();
        $startResponse->assertJson(['ok' => true]);

        $audit = StockAudit::where('location_id', $location->id)->firstOrFail();
        $this->assertSame(2, $audit->expected_total);
        $this->assertTrue($audit->isInProgress());

        // ── Scan screen renders while in progress ───────────────
        $this->get(route('stock-audits.scan', $audit))->assertOk();

        // ── Scan: matched ────────────────────────────────────────
        $matched = $this->postJson(route('stock-audits.scan.store', $audit), ['value' => $lotA]);
        $matched->assertOk();
        $matched->assertJson(['ok' => true, 'result' => 'matched']);
        $this->assertSame(1, $matched->json('scan_counts.matched'));

        // ── Scan: duplicate (same value again) ──────────────────
        $duplicate = $this->postJson(route('stock-audits.scan.store', $audit), ['value' => $lotA]);
        $duplicate->assertOk();
        $duplicate->assertJson(['ok' => true, 'result' => 'duplicate']);

        // ── Scan: unexpected (a value never in this audit) ──────
        $unexpected = $this->postJson(route('stock-audits.scan.store', $audit), ['value' => 'NOT-A-REAL-LOT-CODE']);
        $unexpected->assertOk();
        $unexpected->assertJson(['ok' => true, 'result' => 'unexpected']);

        $audit->refresh();
        $this->assertSame(1, $audit->matched_total);
        $this->assertSame(1, $audit->missingTotal());

        // ── Undo the last scan (the unexpected one) ─────────────
        $undo = $this->postJson(route('stock-audits.undo-scan', $audit));
        $undo->assertOk();
        $undo->assertJson(['ok' => true]);
        $this->assertSame(0, $undo->json('scan_counts.unexpected'));

        // ── Complete with one item still missing ────────────────
        $complete = $this->postJson(route('stock-audits.complete', $audit));
        $complete->assertOk();
        $complete->assertJson(['ok' => true]);

        $audit->refresh();
        $this->assertTrue($audit->isCompleted());
        $this->assertSame(1, $audit->missingTotal());

        // ── Scan screen now redirects (audit no longer in progress) ──
        $afterComplete = $this->get(route('stock-audits.scan', $audit));
        $afterComplete->assertRedirect(route('stock-audits.show', $audit));
        $this->assertStringContainsString(
            'completed',
            session('info') ?? '',
        );

        // ── Details page shows the completed audit ──────────────
        $show = $this->get(route('stock-audits.show', $audit));
        $show->assertOk();
        $show->assertSee($audit->audit_number);

        // ── Write off the remaining missing piece ───────────────
        $writeOff = $this->postJson(route('stock-audits.write-off-missing', $audit));
        $writeOff->assertOk();
        $writeOff->assertJson(['ok' => true]);
        $this->assertStringContainsString('1 missing piece', $writeOff->json('message'));

        // ── Exports render without error ────────────────────────
        $pdf = $this->get(route('stock-audits.export.pdf', $audit));
        $pdf->assertOk();
        $pdf->assertHeader('Content-Type', 'application/pdf');

        $excel = $this->get(route('stock-audits.export.excel', $audit));
        $excel->assertOk();
    }

    public function test_cancelling_an_audit_leaves_stock_untouched_and_blocks_further_scanning(): void
    {
        $location = Location::factory()->create();
        $category = Category::factory()->create();
        $supplier = Supplier::factory()->create();
        $this->purchaseStock($location, $category, $supplier, 1);

        $this->actingAsUserWithPermissions([
            'stock-audits.view', 'stock-audits.create', 'stock-audits.scan', 'stock-audits.complete',
        ]);

        $this->postJson(route('stock-audits.store'), ['location_id' => $location->id])->assertCreated();
        $audit = StockAudit::where('location_id', $location->id)->firstOrFail();

        $cancel = $this->postJson(route('stock-audits.cancel', $audit));
        $cancel->assertOk();
        $cancel->assertJson(['ok' => true]);

        $audit->refresh();
        $this->assertTrue($audit->isCancelled());

        $scanScreen = $this->get(route('stock-audits.scan', $audit));
        $scanScreen->assertRedirect(route('stock-audits.show', $audit));
        $this->assertStringContainsString('cancelled', session('info') ?? '');
    }

    public function test_starting_a_second_overlapping_audit_at_the_same_location_is_rejected(): void
    {
        $location = Location::factory()->create();
        $category = Category::factory()->create();

        $this->actingAsUserWithPermissions(['stock-audits.view', 'stock-audits.create']);

        $this->postJson(route('stock-audits.store'), ['location_id' => $location->id])->assertCreated();

        $second = $this->postJson(route('stock-audits.store'), ['location_id' => $location->id]);
        $second->assertStatus(422);
        $second->assertJson(['ok' => false]);
    }

    public function test_starting_an_audit_without_permission_is_forbidden(): void
    {
        $location = Location::factory()->create();
        $this->actingAsUserWithPermissions([]); // no stock-audits.create

        $this->postJson(route('stock-audits.store'), ['location_id' => $location->id])
            ->assertForbidden();
    }

    public function test_scanning_without_permission_is_forbidden(): void
    {
        $location = Location::factory()->create();
        $this->actingAsUserWithPermissions(['stock-audits.view', 'stock-audits.create']);
        $this->postJson(route('stock-audits.store'), ['location_id' => $location->id])->assertCreated();
        $audit = StockAudit::where('location_id', $location->id)->firstOrFail();

        // Switch to a user with view-only access — no stock-audits.scan.
        $this->actingAsUserWithPermissions(['stock-audits.view']);

        $this->get(route('stock-audits.scan', $audit))->assertForbidden();
        $this->postJson(route('stock-audits.scan.store', $audit), ['value' => 'x'])->assertForbidden();
    }

    public function test_guest_cannot_reach_any_stock_audit_route(): void
    {
        $location = Location::factory()->create();
        $audit = StockAudit::factory()->create(['location_id' => $location->id]);

        $this->get(route('stock-audits.index'))->assertRedirect(route('login'));
        $this->get(route('stock-audits.scan', $audit))->assertRedirect(route('login'));
    }
}
