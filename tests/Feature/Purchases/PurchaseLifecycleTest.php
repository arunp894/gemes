<?php

namespace Tests\Feature\Purchases;

use App\Models\Category;
use App\Models\Location;
use App\Models\Purchase;
use App\Models\PurchaseProduct;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

/**
 * End-to-end coverage of the Purchase workflow through real HTTP routes:
 * creating a purchase with both a "piece" line and a "box" line (which
 * fans out into multiple inventory rows), verifying it actually creates
 * Products, Barcodes and stock_movements (not just Purchase/PurchaseLine
 * rows), posting/cancelling, and the permission gate on every step.
 */
class PurchaseLifecycleTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPermissions;

    private function basePayload(Supplier $supplier, Location $location, Category $category, array $overrides = []): array
    {
        return array_merge([
            'supplier_id'   => $supplier->id,
            'location_id'   => $location->id,
            'purchase_date' => now()->toDateString(),
            'tax_type'      => 'none',
            'status'        => 'draft',
            'lines'         => [
                [
                    'category_id'  => $category->id,
                    'title'        => 'Loose Ruby',
                    'type'         => 'piece',
                    'package_qty'  => 1,
                    'rows'         => [[
                        'qty'          => 3,
                        'carat_weight' => 2.5,
                        'price'        => 1500,
                    ]],
                ],
                [
                    'category_id'  => $category->id,
                    'title'        => 'Boxed Sapphires',
                    'type'         => 'box',
                    'package_qty'  => 2,
                    'rows'         => [
                        ['qty' => 1, 'carat_weight' => 1.2, 'price' => 800],
                        ['qty' => 1, 'carat_weight' => 1.1, 'price' => 800],
                    ],
                ],
            ],
        ], $overrides);
    }

    public function test_creating_a_draft_purchase_generates_products_barcodes_and_lot_codes_but_no_stock_yet(): void
    {
        $supplier = Supplier::factory()->create();
        $location = Location::factory()->create();
        $category = Category::factory()->create();

        $this->actingAsUserWithPermissions(['purchases.create', 'purchases.view']);

        $response = $this->postJson(route('purchases.store'), $this->basePayload($supplier, $location, $category));

        $response->assertCreated();
        $purchase = Purchase::latest('id')->firstOrFail();
        $this->assertTrue($purchase->isDraft());

        // 1 piece line (1 row) + 1 box line (2 rows) = 3 inventory rows/products.
        $purchase->load('lines.rows');
        $rows = $purchase->inventoryRows;
        $this->assertCount(3, $rows);

        foreach ($rows as $row) {
            $this->assertNotNull($row->product_id, 'Each purchase row should have created a real Product.');
            $this->assertNotNull($row->lot_code, 'Each purchase row should have an auto-generated lot code.');
            $this->assertNotNull($row->product?->primaryBarcode, 'Each product should have an auto-generated primary barcode.');
        }

        // Draft purchases must NOT have posted any stock movements yet.
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_posting_a_purchase_writes_stock_in_movements_for_every_row(): void
    {
        $supplier = Supplier::factory()->create();
        $location = Location::factory()->create();
        $category = Category::factory()->create();

        $this->actingAsUserWithPermissions(['purchases.create', 'purchases.post', 'purchases.view']);

        $create = $this->postJson(route('purchases.store'), $this->basePayload($supplier, $location, $category, ['status' => 'draft']));
        $create->assertCreated();
        $purchase = Purchase::latest('id')->firstOrFail();

        $post = $this->patchJson(route('purchases.post', $purchase));
        $post->assertOk();

        $purchase->refresh();
        $this->assertTrue($purchase->isPosted());

        // 3 rows total (1 piece qty handled as a single row + 2 box rows) -> 3 IN movements.
        $this->assertDatabaseCount('stock_movements', 3);
        $this->assertSame(
            3,
            \App\Models\StockMovement::where('reason', 'purchase')->where('location_id', $location->id)->count()
        );
    }

    public function test_creating_a_purchase_directly_as_posted_also_writes_stock_movements(): void
    {
        $supplier = Supplier::factory()->create();
        $location = Location::factory()->create();
        $category = Category::factory()->create();

        $this->actingAsUserWithPermissions(['purchases.create']);

        $response = $this->postJson(route('purchases.store'), $this->basePayload($supplier, $location, $category, ['status' => 'posted']));

        $response->assertCreated();
        $purchase = Purchase::latest('id')->firstOrFail();
        $this->assertTrue($purchase->isPosted());
        $this->assertDatabaseCount('stock_movements', 3);
    }

    public function test_cancelling_a_posted_purchase_reverses_its_stock(): void
    {
        $supplier = Supplier::factory()->create();
        $location = Location::factory()->create();
        $category = Category::factory()->create();

        $this->actingAsUserWithPermissions(['purchases.create', 'purchases.edit']);

        $this->postJson(route('purchases.store'), $this->basePayload($supplier, $location, $category, ['status' => 'posted']))
            ->assertCreated();
        $purchase = Purchase::latest('id')->firstOrFail();

        $cancel = $this->patchJson(route('purchases.cancel', $purchase));
        $cancel->assertOk();

        $purchase->refresh();
        $this->assertTrue($purchase->isCancelled());

        // Cancelling a posted purchase must reverse the stock it added:
        // 3 original IN movements + 3 matching OUT/cancel movements.
        $this->assertSame(3, \App\Models\StockMovement::where('direction', 'in')->count());
        $this->assertSame(3, \App\Models\StockMovement::where('direction', 'out')->count());
    }

    public function test_creating_a_purchase_without_permission_is_forbidden(): void
    {
        $supplier = Supplier::factory()->create();
        $location = Location::factory()->create();
        $category = Category::factory()->create();

        $this->actingAsUserWithPermissions([]);

        $this->postJson(route('purchases.store'), $this->basePayload($supplier, $location, $category))
            ->assertForbidden();
        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_a_user_with_only_view_permission_cannot_create_a_purchase(): void
    {
        $supplier = Supplier::factory()->create();
        $location = Location::factory()->create();
        $category = Category::factory()->create();

        // Regression guard for the resource-route middleware bug where every
        // action on a resource required ALL of view+create+edit+delete
        // instead of being gated per-action.
        $this->actingAsUserWithPermissions(['purchases.view']);

        $this->get(route('purchases.index'))->assertOk();
        $this->postJson(route('purchases.store'), $this->basePayload($supplier, $location, $category))
            ->assertForbidden();
    }

    public function test_guest_cannot_reach_purchases_index(): void
    {
        $this->get(route('purchases.index'))->assertRedirect(route('login'));
    }
}
