<?php

namespace Tests\Feature\Products;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPermissions;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title'            => 'Test Ruby',
            'sku'              => 'TEST-SKU-' . fake()->unique()->numberBetween(10000, 999999),
            'status'           => 1,
            'website_enabled'  => 0,
            'featured_product' => 0,
        ], $overrides);
    }

    public function test_a_permitted_user_can_create_a_product(): void
    {
        $this->actingAsUserWithPermissions(['products.create', 'products.view']);

        $response = $this->postJson(route('products.store'), $this->validPayload(['sku' => 'CREATE-TEST-001']));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('products', ['sku' => 'CREATE-TEST-001', 'title' => 'Test Ruby']);
    }

    public function test_creating_a_product_without_permission_is_forbidden(): void
    {
        $this->actingAsUserWithPermissions([]);

        $response = $this->postJson(route('products.store'), $this->validPayload());

        $response->assertForbidden();
        $this->assertDatabaseCount('products', 0);
    }

    public function test_duplicate_sku_is_rejected(): void
    {
        Product::factory()->create(['sku' => 'DUPLICATE-SKU']);
        $this->actingAsUserWithPermissions(['products.create']);

        $response = $this->postJson(route('products.store'), $this->validPayload(['sku' => 'DUPLICATE-SKU']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('sku');
    }

    public function test_toggling_website_visibility_on_requires_a_primary_image(): void
    {
        $product = Product::factory()->create(['website_enabled' => false]);
        $this->actingAsUserWithPermissions(['products.toggle-website']);

        $response = $this->patchJson(route('products.toggle-website', $product));

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertFalse($product->fresh()->website_enabled);
    }

    public function test_toggling_website_visibility_requires_permission(): void
    {
        $product = Product::factory()->create(['website_enabled' => false]);
        $this->actingAsUserWithPermissions([]); // no products.toggle-website

        $this->patchJson(route('products.toggle-website', $product))->assertForbidden();
    }

    public function test_toggling_status_flips_active_flag(): void
    {
        $product = Product::factory()->create(['status' => Product::STATUS_ACTIVE]);
        $this->actingAsUserWithPermissions(['products.edit']);

        $response = $this->patchJson(route('products.toggle-status', $product));

        $response->assertOk();
        $response->assertJson(['success' => true, 'status' => false]);
        $this->assertSame(0, (int) $product->fresh()->status);
    }

    public function test_index_page_requires_products_view_permission(): void
    {
        $this->actingAsUserWithPermissions([]);
        $this->get(route('products.index'))->assertForbidden();

        $this->actingAsUserWithPermissions(['products.view']);
        $this->get(route('products.index'))->assertOk();
    }

    public function test_guest_cannot_reach_products_index(): void
    {
        $this->get(route('products.index'))->assertRedirect(route('login'));
    }
}
