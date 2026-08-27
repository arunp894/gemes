<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class DashboardPermissionTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPermissions;

    public function test_super_admin_sees_every_dashboard_section(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('canSales', true);
        $response->assertViewHas('canPurchases', true);
        $response->assertViewHas('canProducts', true);
        $response->assertViewHas('canCustomers', true);
        $response->assertViewHas('canSuppliers', true);
        $response->assertViewHas('canStock', true);

        $response->assertSee('Sales This Month', false);
        $response->assertSee('Purchases This Month', false);
        $response->assertSee('New Sale', false);
        $response->assertSee('New Purchase', false);
    }

    public function test_user_with_no_permissions_sees_no_gated_sections(): void
    {
        $this->actingAsUserWithPermissions([]);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('canSales', false);
        $response->assertViewHas('canPurchases', false);
        $response->assertViewHas('canProducts', false);
        $response->assertViewHas('canCustomers', false);
        $response->assertViewHas('canSuppliers', false);
        $response->assertViewHas('canStock', false);

        $response->assertDontSee('New Sale', false);
        $response->assertDontSee('New Purchase', false);
        $response->assertDontSee('Add Product', false);
    }

    public function test_products_view_permission_only_shows_the_products_section(): void
    {
        Product::factory()->count(3)->create(['status' => Product::STATUS_ACTIVE]);
        Supplier::factory()->create();

        $this->actingAsUserWithPermissions(['products.view']);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('canProducts', true);
        $response->assertViewHas('canSales', false);
        $response->assertViewHas('canPurchases', false);
        $response->assertViewHas('canSuppliers', false);

        // The controller must not have zeroed-out products data while a
        // permitted user is looking at it.
        $response->assertViewHas('totalProducts', 3);
    }

    public function test_sales_create_button_requires_sales_create_permission(): void
    {
        // Has sales.view (sees the KPI/section) but not sales.create.
        $this->actingAsUserWithPermissions(['sales.view']);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('New Sale', false);
    }

    public function test_quick_actions_button_appears_only_with_matching_create_permission(): void
    {
        $this->actingAsUserWithPermissions(['purchases.create']);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('New Purchase', false);
        $response->assertDontSee('New Sale', false);
        $response->assertDontSee('Add Product', false);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }
}
