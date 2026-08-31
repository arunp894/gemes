<?php

namespace Tests\Feature\Stock;

use App\Models\Category;
use App\Models\Location;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

/**
 * Regression coverage for the unified Stock Movement ledger — the
 * consolidated view that replaced the separate Sales Report tab so every
 * inventory change (purchase, sale, transfer, adjustment) shows up in one
 * place with a clear reference number.
 *
 * The reference-number search once silently broke: Yajra's filterColumn()
 * is looked up by a DataTables column's `name` attribute, not its `data`
 * key, when the two differ — a mismatch here doesn't error, it just
 * quietly falls back to Yajra's own single-column search and drops the
 * custom multi-field match. These tests exercise the real HTTP endpoint
 * with a properly-shaped DataTables request so that class of bug shows up
 * as a failing assertion instead of a silently degraded search box.
 */
class StockMovementTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPermissions;

    private function dataTablesColumns(): array
    {
        $blank = ['searchable' => 'false', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']];

        return [
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex'] + $blank,
            ['data' => 'when_label', 'name' => 'when_label'] + $blank,
            ['data' => 'product_label', 'name' => 'product_label', 'searchable' => 'true', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']],
            ['data' => 'movement_label', 'name' => 'movement_label'] + $blank,
            ['data' => 'reference_label', 'name' => 'reference_label'] + $blank,
            ['data' => 'location_label', 'name' => 'location_label'] + $blank,
            ['data' => 'qty_label', 'name' => 'qty_label'] + $blank,
            ['data' => 'by_label', 'name' => 'by_label'] + $blank,
        ];
    }

    private function movementsRequest(array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->getJson(route('stock.movements-data', array_merge([
            'draw' => 1, 'start' => 0, 'length' => 25,
            'columns' => $this->dataTablesColumns(),
            'search' => ['value' => $extra['q'] ?? '', 'regex' => 'false'],
        ], $extra)));
    }

    private function postAndSellOnePiece(Location $location, Category $category, Supplier $supplier): void
    {
        $purchase = app(PurchaseService::class)->create([
            'supplier_id'   => $supplier->id,
            'location_id'   => $location->id,
            'purchase_date' => now()->toDateString(),
            'tax_type'      => 'none',
            'status'        => 'posted',
            'lines'         => [[
                'category_id'  => $category->id,
                'title'        => 'Test Stone',
                'type'         => 'piece',
                'package_qty'  => 1,
                'rows'         => [['qty' => 1, 'carat_weight' => 1.0, 'price' => 1000]],
            ]],
        ]);

        $row = $purchase->load('lines.rows')->inventoryRows->first();
        $customer = \App\Models\Customer::factory()->create();

        app(\App\Services\SaleService::class)->create([
            'customer_id' => $customer->id,
            'location_id' => $location->id,
            'tax_type'    => 'none',
            'status'      => 'posted',
            'lines'       => [[
                'product_id'          => $row->product_id,
                'purchase_product_id' => $row->id,
                'qty'                 => 1,
                'unit_price'          => 2000,
            ]],
        ]);
    }

    public function test_movements_ledger_shows_both_purchase_and_sale_as_movements(): void
    {
        $location = Location::factory()->create();
        $category = Category::factory()->create();
        $supplier = Supplier::factory()->create();
        $this->postAndSellOnePiece($location, $category, $supplier);

        $this->actingAsUserWithPermissions(['stock.view']);

        $response = $this->movementsRequest();
        $response->assertOk();
        $response->assertJsonPath('recordsTotal', 2);
    }

    public function test_type_filters_isolate_purchase_and_sale_movements(): void
    {
        $location = Location::factory()->create();
        $category = Category::factory()->create();
        $supplier = Supplier::factory()->create();
        $this->postAndSellOnePiece($location, $category, $supplier);

        $this->actingAsUserWithPermissions(['stock.view']);

        $this->movementsRequest(['type' => 'purchase'])->assertJsonPath('recordsFiltered', 1);
        $this->movementsRequest(['type' => 'sale'])->assertJsonPath('recordsFiltered', 1);
        $this->movementsRequest(['type' => 'in'])->assertJsonPath('recordsFiltered', 1);
        $this->movementsRequest(['type' => 'out'])->assertJsonPath('recordsFiltered', 1);
    }

    /**
     * Regression test for the Yajra name/data column-key mismatch: search
     * must match the purchase's reference number (invoice_number), not
     * just the product title/SKU that a naive default search would find.
     */
    public function test_search_matches_the_purchase_reference_number(): void
    {
        $location = Location::factory()->create();
        $category = Category::factory()->create();
        $supplier = Supplier::factory()->create();
        $this->postAndSellOnePiece($location, $category, $supplier);

        $this->actingAsUserWithPermissions(['stock.view']);

        $purchase = \App\Models\Purchase::firstOrFail();

        $byReference = $this->movementsRequest(['q' => $purchase->invoice_number]);
        $byReference->assertOk();
        $byReference->assertJsonPath('recordsFiltered', 1);

        $noMatch = $this->movementsRequest(['q' => 'NOT-A-REAL-REFERENCE-XYZ']);
        $noMatch->assertJsonPath('recordsFiltered', 0);
    }

    public function test_reference_link_points_to_the_purchase_show_page(): void
    {
        $location = Location::factory()->create();
        $category = Category::factory()->create();
        $supplier = Supplier::factory()->create();
        $this->postAndSellOnePiece($location, $category, $supplier);

        $this->actingAsUserWithPermissions(['stock.view']);

        $purchase = \App\Models\Purchase::firstOrFail();
        $response = $this->movementsRequest(['type' => 'purchase']);
        $response->assertOk();

        $html = $response->json('data.0.reference_label');
        $this->assertStringContainsString(route('purchases.show', $purchase), $html);
        $this->assertStringContainsString($purchase->invoice_number, $html);
    }

    public function test_movement_page_requires_stock_view_permission(): void
    {
        $this->actingAsUserWithPermissions([]);

        $this->get(route('stock.index'))->assertForbidden();
        $this->movementsRequest()->assertForbidden();
    }

    public function test_guest_cannot_reach_stock_movements(): void
    {
        $this->get(route('stock.index'))->assertRedirect(route('login'));
    }
}
