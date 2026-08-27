<?php

namespace Tests\Feature\Sales;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

/**
 * End-to-end coverage of the Sale workflow through real HTTP routes:
 * selling real posted-purchase stock, verifying the OUT stock movement
 * gets written, rejecting a sale that would oversell available stock,
 * and the permission gate on every step.
 */
class SaleLifecycleTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPermissions;

    /** Create one posted purchase with a single piece row (qty 2) at $location. */
    private function purchaseStock(Location $location, Category $category, Supplier $supplier, int $qty = 2): Purchase
    {
        $purchase = app(PurchaseService::class)->create([
            'supplier_id'   => $supplier->id,
            'location_id'   => $location->id,
            'purchase_date' => now()->toDateString(),
            'tax_type'      => 'none',
            'status'        => 'posted',
            'lines'         => [[
                'category_id'  => $category->id,
                'title'        => 'Sellable Stone',
                'type'         => 'piece',
                'package_qty'  => 1,
                'rows'         => [[
                    'qty'          => $qty,
                    'carat_weight' => 1.0,
                    'price'        => 1000,
                ]],
            ]],
        ]);

        return $purchase->load('lines.rows');
    }

    public function test_posting_a_sale_decrements_stock_for_the_sold_quantity(): void
    {
        $location = Location::factory()->create();
        $category = Category::factory()->create();
        $supplier = Supplier::factory()->create();
        $customer = Customer::factory()->create();
        $purchase = $this->purchaseStock($location, $category, $supplier, 5);
        $row      = $purchase->inventoryRows->first();

        $this->actingAsUserWithPermissions(['sales.create', 'sales.view']);

        $response = $this->postJson(route('sales.store'), [
            'sale_date'   => now()->toDateString(),
            'customer_id' => $customer->id,
            'location_id' => $location->id,
            'tax_type'    => 'none',
            'status'      => 'posted',
            'lines'       => [[
                'product_id'          => $row->product_id,
                'purchase_product_id' => $row->id,
                'qty'                 => 2,
                'unit_price'          => 2000,
            ]],
        ]);

        $response->assertCreated();
        $response->assertJson(['ok' => true]);

        $this->assertSame(
            2,
            (int) StockMovement::where('purchase_product_id', $row->id)
                ->where('direction', 'out')
                ->sum('qty')
        );
    }

    public function test_selling_more_than_available_stock_is_rejected(): void
    {
        $location = Location::factory()->create();
        $category = Category::factory()->create();
        $supplier = Supplier::factory()->create();
        $customer = Customer::factory()->create();
        $purchase = $this->purchaseStock($location, $category, $supplier, 1); // only 1 on hand
        $row      = $purchase->inventoryRows->first();

        $this->actingAsUserWithPermissions(['sales.create']);

        $response = $this->postJson(route('sales.store'), [
            'sale_date'   => now()->toDateString(),
            'customer_id' => $customer->id,
            'location_id' => $location->id,
            'tax_type'    => 'none',
            'status'      => 'posted',
            'lines'       => [[
                'product_id'          => $row->product_id,
                'purchase_product_id' => $row->id,
                'qty'                 => 99, // far more than the 1 piece on hand
                'unit_price'          => 2000,
            ]],
        ]);

        $response->assertStatus(422);
        $response->assertJson(['ok' => false]);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_a_draft_sale_does_not_touch_stock_until_posted(): void
    {
        $location = Location::factory()->create();
        $category = Category::factory()->create();
        $supplier = Supplier::factory()->create();
        $customer = Customer::factory()->create();
        $purchase = $this->purchaseStock($location, $category, $supplier, 3);
        $row      = $purchase->inventoryRows->first();

        $this->actingAsUserWithPermissions(['sales.create']);

        $response = $this->postJson(route('sales.store'), [
            'sale_date'   => now()->toDateString(),
            'customer_id' => $customer->id,
            'location_id' => $location->id,
            'tax_type'    => 'none',
            'status'      => 'draft',
            'lines'       => [[
                'product_id'          => $row->product_id,
                'purchase_product_id' => $row->id,
                'qty'                 => 2,
                'unit_price'          => 2000,
            ]],
        ]);

        $response->assertCreated();
        $this->assertSame(0, StockMovement::where('direction', 'out')->count());
    }

    public function test_creating_a_sale_without_permission_is_forbidden(): void
    {
        $location = Location::factory()->create();
        $customer = Customer::factory()->create();
        $this->actingAsUserWithPermissions([]);

        $this->postJson(route('sales.store'), [
            'sale_date'   => now()->toDateString(),
            'customer_id' => $customer->id,
            'location_id' => $location->id,
            'tax_type'    => 'none',
            'status'      => 'draft',
            'lines'       => [],
        ])->assertForbidden();
    }

    public function test_a_user_with_only_view_permission_cannot_create_a_sale(): void
    {
        $location = Location::factory()->create();
        $customer = Customer::factory()->create();

        // Regression guard for the resource-route middleware bug where every
        // action on a resource required ALL of view+create+edit+delete
        // instead of being gated per-action.
        $this->actingAsUserWithPermissions(['sales.view']);

        $this->get(route('sales.index'))->assertOk();
        $this->postJson(route('sales.store'), [
            'sale_date'   => now()->toDateString(),
            'customer_id' => $customer->id,
            'location_id' => $location->id,
            'tax_type'    => 'none',
            'status'      => 'draft',
            'lines'       => [],
        ])->assertForbidden();
    }

    public function test_guest_cannot_reach_sales_index(): void
    {
        $this->get(route('sales.index'))->assertRedirect(route('login'));
    }
}
