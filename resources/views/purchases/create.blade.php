@extends('layout.app')

@section('title', 'New Purchase')

@section('content')
<div class="container-fluid" id="purchaseFormApp">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">New Purchase</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
                <li class="breadcrumb-item active">New</li>
            </ol>
        </div>
    </div>

    <form id="purchaseForm" novalidate @submit.prevent="submit(false)" :class="{ 'was-validated': wasValidated }">

        <div class="row g-3">

            {{-- ╔════════════════════════════════════════════════════╗
                                 LEFT  COLUMN
                ╚════════════════════════════════════════════════════╝ --}}
            <div class="col-xl-9">

                {{-- ──────── Header card ──────── --}}
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-md-3">
                                <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                <select class="form-select" v-model.number="form.supplier_id"
                                        :class="{ 'is-invalid': errors.supplier_id }"
                                        @change="onSupplierChange" required>
                                    <option :value="null">— Select supplier —</option>
                                    <option v-for="s in suppliers" :key="s.id" :value="s.id">
                                        @{{ s.company_name || s.name }} (@{{ s.supplier_code }})
                                    </option>
                                </select>
                                <div class="invalid-feedback">@{{ errors.supplier_id }}</div>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Purchase Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" v-model="form.purchase_date"
                                       :class="{ 'is-invalid': errors.purchase_date }"
                                       @change="refreshInvoiceNumber" required>
                                <div class="invalid-feedback">@{{ errors.purchase_date }}</div>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Invoice #</label>
                                <input type="text" class="form-control bg-light" :value="form.invoice_number_preview"
                                       readonly placeholder="auto-generated">
                                <small class="text-muted"><code>PREFIX-YYYYMM-####</code></small>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Tax Type <span class="text-danger">*</span></label>
                                <select class="form-select" v-model="form.tax_type">
                                    <option value="none">No Tax</option>
                                    <option value="cgst_sgst">CGST + SGST</option>
                                    <option value="igst">IGST</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Location <span class="text-danger">*</span></label>
                                <select class="form-select" v-model.number="form.location_id"
                                        :class="{ 'is-invalid': errors.location_id }"
                                        required>
                                    <option :value="null">&mdash; Select location &mdash;</option>
                                    <option v-for="loc in locations" :key="loc.id" :value="loc.id">
                                        @{{ loc.name }} <span v-if="loc.location_code">&middot; @{{ loc.location_code }}</span>
                                    </option>
                                </select>
                                <div class="invalid-feedback">@{{ errors.location_id }}</div>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- ──────── Add Item ──────── --}}
                <div class="card">
                    <div class="card-header border-light d-flex align-items-center gap-2">
                        <i class="ti ti-plus fs-18 text-primary"></i>
                        <h5 class="card-title mb-0">Add Item</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">

                            <div class="col-md-2">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" v-model.number="addForm.category_id"
                                        :class="{ 'is-invalid': addErrors.category_id }"
                                        :disabled="!form.supplier_id">
                                    <option :value="null">— Select category —</option>
                                    <option v-for="c in categoryOptions" :key="c.id" :value="c.id">@{{ c.name }}</option>
                                </select>
                                <div class="invalid-feedback">@{{ addErrors.category_id }}</div>
                                <small class="text-muted" v-if="!form.supplier_id">Pick a supplier first.</small>
                                <small class="text-muted" v-else-if="supplierCategories.length">Filtered to this supplier.</small>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" v-model="addForm.title" maxlength="200"
                                       :class="{ 'is-invalid': addErrors.title }"
                                       placeholder="e.g. Paraiba Tourmaline, loose">
                                <div class="invalid-feedback">@{{ addErrors.title }}</div>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Type</label>
                                <select class="form-select" v-model="addForm.type" @change="onAddFormTypeChange">
                                    <option value="piece">Piece</option>
                                    <option value="box">Box</option>
                                </select>
                            </div>

                            <div class="col-md-1">
                                <label class="form-label">Qty <span class="text-danger">*</span></label>
                                <input type="number" min="1" class="form-control" v-model.number="addForm.package_qty"
                                       :disabled="addForm.type === 'piece'"
                                       :class="{ 'is-invalid': addErrors.package_qty }">
                                <div class="invalid-feedback">@{{ addErrors.package_qty }}</div>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Country of Origin</label>
                                <select class="form-select" v-model.number="addForm.country_of_origin_id">
                                    <option :value="null">— Select —</option>
                                    <option v-for="o in countriesOfOrigin" :key="o.id" :value="o.id">@{{ o.name }}</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Selling Price</label>
                                <input type="number" step="0.01" min="0" class="form-control"
                                       v-model.number="addForm.website_price" placeholder="optional">
                                <small class="text-muted">Set later if unsure.</small>
                            </div>

                            {{-- Gemstone panel: shown only when the chosen category is flagged is_gemstone --}}
                            <template v-if="addFormIsGemstone">
                                <div class="col-12"><hr class="my-1"></div>
                                <div class="col-md-2">
                                    <label class="form-label">Carat Weight <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.001" min="0.001" class="form-control"
                                               v-model.number="addForm.carat_weight"
                                               :class="{ 'is-invalid': addErrors.carat_weight }">
                                        <span class="input-group-text">ct</span>
                                        <div class="invalid-feedback">@{{ addErrors.carat_weight }}</div>
                                    </div>
                                    <small class="text-muted">Default; editable per row below.</small>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Stone Type <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="addForm.stone_type"
                                            :class="{ 'is-invalid': addErrors.stone_type }">
                                        <option :value="null">— Select —</option>
                                        @foreach (\App\Models\Product::STONE_TYPES as $type)
                                            <option value="{{ $type }}">{{ $type }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">@{{ addErrors.stone_type }}</div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Treatment <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="addForm.treatment"
                                            :class="{ 'is-invalid': addErrors.treatment }">
                                        <option :value="null">— Select —</option>
                                        @foreach (\App\Models\Product::TREATMENTS as $treatment)
                                            <option value="{{ $treatment }}">{{ $treatment }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">@{{ addErrors.treatment }}</div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Cut / Shape</label>
                                    <select class="form-select" v-model="addForm.cut_shape">
                                        <option :value="null">— Select —</option>
                                        @foreach (\App\Models\Product::CUT_SHAPES as $shape)
                                            <option value="{{ $shape }}">{{ $shape }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Clarity Grade</label>
                                    <select class="form-select" v-model="addForm.clarity_grade">
                                        <option :value="null">— Select —</option>
                                        @foreach (\App\Models\Product::CLARITY_GRADES as $clarity)
                                            <option value="{{ $clarity }}">{{ $clarity }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Colour Grade</label>
                                    <input type="text" class="form-control" v-model="addForm.colour_grade"
                                           maxlength="100" placeholder="e.g. Vivid Blue">
                                </div>
                            </template>

                            <div class="col-12 d-flex align-items-center gap-2 mt-2">
                                <button type="button" class="btn btn-primary" @click="addLine">
                                    <i class="ti ti-plus me-1"></i> Add to Purchase
                                </button>
                                <small class="text-muted" v-if="addForm.package_qty > 1">
                                    Creates @{{ addForm.package_qty }} separate products, one per @{{ addForm.type }} — each gets its own photos and listing afterward.
                                </small>
                            </div>

                            <div class="col-12" v-if="formMessage">
                                <div class="alert mb-0 py-2" :class="formAlertClass">
                                    <i :class="formIconClass"></i>
                                    @{{ formMessage }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ──────── Lines table ──────── --}}
                @include('purchases._partials._line_table')

            </div>{{-- /col-xl-9 --}}

            {{-- ╔════════════════════════════════════════════════════╗
                                  RIGHT COLUMN — SUMMARY
                ╚════════════════════════════════════════════════════╝ --}}
            <div class="col-xl-3">
                @include('purchases._partials._summary_card')
            </div>

        </div>
    </form>
</div>
@endsection

@push('scripts')
@include('purchases._partials._purchase_app_script', [
    'mode'            => 'create',
    'suppliersJson'   => $suppliers->toJson(),
    'locationsJson'   => $locations->toJson(),
    'racksJson'       => $racks->toJson(),
    'categoriesJson'  => $categories->toJson(),
    'countriesOfOriginJson' => $countriesOfOrigin->toJson(),
    'previewUrl'      => route('purchases.preview-invoice-number'),
    'lotCodePreviewUrl' => route('purchases.preview-lot-code'),
    'submitUrl'       => route('purchases.store'),
    'submitMethod'    => 'POST',
    'existingPurchase'=> null,
])
@endpush
