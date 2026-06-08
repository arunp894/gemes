{{-- Website Visibility Panel.
     Toggle + optional per-website overrides (price, title, description).
     Featured-product checkbox is auto-disabled when website is disabled
     (mirrors the server-side `booted()` behaviour in Product model).
--}}

<div class="card mb-3 d-none">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="ti ti-world me-1"></i>Website Visibility</h5>
    </div>
    <div class="card-body">

        {{-- The main toggle --}}
        <div class="form-check form-switch form-switch-lg mb-3">
            <input class="form-check-input" type="checkbox" role="switch"
                id="website_enabled" name="website_enabled"
                v-model="form.website_enabled">
            <label class="form-check-label" for="website_enabled">
                <span class="fw-semibold">
                    @{{ form.website_enabled ? 'Enabled' : 'Disabled' }}
                </span>
                — @{{ form.website_enabled
                    ? 'Product is published and visible on the website.'
                    : 'Product is hidden from the website entirely.' }}
            </label>
        </div>

        @if (!$product)
            <p class="alert alert-light small mb-3">
                <i class="ti ti-info-circle me-1"></i>
                A Primary Image is required before website visibility can be enabled.
            </p>
        @endif

        {{-- Featured Product checkbox (only visible when website is enabled) --}}
        <div class="form-check mb-3" v-show="form.website_enabled">
            <input class="form-check-input" type="checkbox" id="featured_product"
                name="featured_product" v-model="form.featured_product">
            <label class="form-check-label" for="featured_product">
                <i class="ti ti-star-filled text-warning me-1"></i>
                <strong>Featured Product</strong> — appears on the homepage featured section
            </label>
        </div>

        {{-- Override fields (only editable when enabled) --}}
        <div class="row g-3" :class="{ 'opacity-50': !form.website_enabled }">

            <div class="col-md-6">
                <label for="website_price" class="form-label">Website Price (override)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="website_price"
                    name="website_price" v-model="form.website_price"
                    :disabled="!form.website_enabled" placeholder="Leave blank to use base price">
                <small class="text-muted">Optional — if blank, the base Selling Price is used.</small>
            </div>

            <div class="col-md-6">
                <label for="website_sort_order" class="form-label">Website Sort Order</label>
                <input type="number" min="0" max="99999" class="form-control" id="website_sort_order"
                    name="website_sort_order" v-model="form.website_sort_order"
                    :disabled="!form.website_enabled" placeholder="0">
                <small class="text-muted">Position within the subcategory listing page.</small>
            </div>

            <div class="col-md-12">
                <label for="website_title" class="form-label">Website Title (override)</label>
                <input type="text" class="form-control" id="website_title" name="website_title"
                    v-model="form.website_title" maxlength="200"
                    :disabled="!form.website_enabled"
                    placeholder="Leave blank to use the main product title">
            </div>

            <div class="col-md-12">
                <label for="website_description" class="form-label">Website Description (override)</label>
                <textarea class="form-control" id="website_description" name="website_description"
                    v-model="form.website_description" rows="3"
                    :disabled="!form.website_enabled"
                    placeholder="Leave blank to use the main full description"></textarea>
            </div>

        </div>
    </div>
</div>
