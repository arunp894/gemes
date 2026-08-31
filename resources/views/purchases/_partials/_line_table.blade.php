{{-- Inline multi-row product table. There is no separate "Add Item" form —
     the "Add Row" button (and the empty-state one) push a blank line
     straight into this table via addBlankLine(); its Stone and Country of
     Origin cells render as live selects for as long as the line is new
     (`!line.id`, i.e. not yet saved). Once a line has been saved its
     Stone/Country cells go read-only (see class docblock in the script
     partial for why: nothing re-syncs a changed category to the product
     it already created). Gemstone grading fields (stone type, treatment,
     cut, clarity, colour, description) aren't collected here at all —
     they're set later by editing the individual Product, same as photos.

     Layout strategy (driven by `line.rows.length`, not `line.type`):
       - Each LINE renders a "parent" <tr> with the item's title/category
         and country of origin. The Type column carries both the
         Box/Piece select and, for Box lines, a Pack Qty input — changing
         either calls rebuildRows(), which fans the line's rows out (or
         back in) to match. PIECE lines are always exactly one row;
         several identical pieces can share one product via that row's
         own Qty field instead.
       - When a line has exactly ONE row, the parent row also carries that
         row's qty / carat / barcode / rack / price / website inputs inline.
       - When a line has MORE THAN ONE row, the parent row instead shows
         aggregate readouts (Total pieces / Inventory rows / Line total)
         with a chevron to expand/collapse. When expanded, CHILD <tr>s are
         emitted directly below the parent INSIDE THE SAME <tbody>, one per
         inventory row. Child rows use a faint left indent so the hierarchy
         is obvious without nested tables.
       - A row that already created a product (edit mode, `row._product`
         set) shows its SKU as a small badge instead of "New" — that
         product is reused on save, not recreated.
--}}

<div class="card">
    <div class="card-header border-light d-flex align-items-center gap-2">
        <i class="ti ti-list-details fs-18 text-primary"></i>
        <h5 class="card-title mb-0">Purchase Items</h5>
        <span class="badge badge-soft-primary ms-2">@{{ form.lines.length }} lines</span>
        <button type="button" class="btn btn-sm btn-success ms-auto" @click="addBlankLine"
                :disabled="!form.supplier_id" :title="!form.supplier_id ? 'Pick a supplier first' : ''">
            <i class="ti ti-plus me-1"></i> Add Row
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0 purchase-line-table">
            <thead class="bg-light bg-opacity-25 text-uppercase fs-xxs">
                <tr>
                    <th style="width: 32px;">#</th>
                    <th style="min-width: 240px;"><i class="ti ti-diamond me-1"></i>Item <span class="text-danger">*</span></th>
                    <th style="width: 140px;"><i class="ti ti-map-pin me-1"></i>Origin</th>
                    <th style="width: 130px;"><i class="ti ti-box me-1"></i>Type</th>
                    <th style="width: 90px;"><i class="ti ti-stack-2 me-1"></i>Pcs <span class="text-danger">*</span></th>
                    <th style="width: 100px;"><i class="ti ti-scale me-1"></i>Carat</th>
                    <th style="width: 100px;"><i class="ti ti-barcode me-1"></i>Barcode</th>
                    <th style="width: 130px;" title="Lot Code"><i class="ti ti-tag me-1"></i>Lot</th>
                    {{-- Rack column hidden --}}
                    <th style="width: 110px;"><i class="ti ti-cash me-1"></i>Price <span class="text-danger">*</span></th>
                    <th style="width: 100px;" title="Selling Price"><i class="ti ti-world me-1"></i>S.Price</th>
                    {{-- Tax % and Disc % hidden --}}
                    <th style="width: 70px;" class="text-end" title="Line Total"><i class="ti ti-calculator me-1"></i>Total</th>
                    <th style="width: 40px;"></th>
                </tr>
            </thead>

            <tbody>
                <template v-for="(line, li) in form.lines">

                    {{-- ═══════ PARENT ROW (always rendered) ═══════ --}}
                    <tr :key="'l-' + li" class="line-parent"
                        :class="{ 'line-highlight': line._highlight, 'line-has-error': hasLineError(li) }">

                        <td class="text-muted small">
    <div class="d-flex align-items-center gap-1">
        <button v-if="line.rows.length > 1" type="button"
                class="btn btn-sm btn-link p-0 text-muted lh-1"
                @click="toggleExpand(li)"
                :title="line._expanded ? 'Collapse rows' : 'Expand rows'">
            <i class="ti fs-16"
               :class="line._expanded ? 'ti-chevron-down' : 'ti-chevron-right'"></i>
        </button>
        <span>@{{ li + 1 }}</span>
    </div>
</td>

                        <td>
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                {{-- New (unsaved) lines get a live Stone picker right in the
                                     row — persisted lines stay read-only here (changing an
                                     already-purchased line's category wouldn't reach the
                                     product it already created, so it's not offered). --}}
                                <div v-if="!line.id" style="min-width: 140px;">
                                    <select v-select2 data-placeholder="— Select Stone —"
                                            class="form-select form-select-sm"
                                            :class="{ 'is-invalid': lineError(li, 'category_id') }"
                                            v-model.number="line.category_id"
                                            @change="onLineCategoryChange(li)" required>
                                        <option :value="null">— Select Stone —</option>
                                        <option v-for="c in categoryOptions" :key="c.id" :value="c.id">@{{ c.name }}</option>
                                    </select>
                                    <div class="invalid-feedback d-block small" v-if="lineError(li, 'category_id')">@{{ lineError(li, 'category_id') }}</div>
                                </div>
                                <div v-else class="text-truncate" style="min-width: 0;"
                                     :title="line.title + (categoryName(line.category_id) ? ' — ' + categoryName(line.category_id) : '')">
                                    <div class="fw-semibold text-truncate">@{{ line.title }}</div>
                                    <small class="text-muted text-truncate d-block">@{{ categoryName(line.category_id) }}</small>
                                </div>
                                {{-- Single-row lines: the toggle edits row[0] directly
                                     (same "hoist row 0 into the parent row" pattern the
                                     other inputs already follow). Multi-row lines hide
                                     this — one flag can't represent N independently-set
                                     rows — and get a toggle per child row instead. --}}
                                <button v-if="line.rows.length === 1" type="button"
                                        class="btn btn-icon btn-sm flex-shrink-0"
                                        :class="line.rows[0].website_enabled ? 'btn-soft-info' : 'btn-default'"
                                        @click="toggleRowWebsite(line.rows[0])"
                                        :title="line.rows[0].website_enabled ? 'Listed on website — click to unlist' : 'Not listed — click to list on website'">
                                    <i class="ti fs-16" :class="line.rows[0].website_enabled ? 'ti-world' : 'ti-world-off'"></i>
                                </button>
                            </div>
                        </td>

                        <td class="small">
                            <select v-if="!line.id" v-select2 data-placeholder="— Select —"
                                    class="form-select form-select-sm" v-model.number="line.country_of_origin_id">
                                <option :value="null">— Select —</option>
                                <option v-for="o in countriesOfOrigin" :key="o.id" :value="o.id">@{{ o.name }}</option>
                            </select>
                            <span v-else class="text-truncate d-inline-block" style="max-width: 120px;"
                                  :title="countryOfOriginName(line.country_of_origin_id)">@{{ countryOfOriginName(line.country_of_origin_id) }}</span>
                        </td>

                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn"
                                            :class="line.type === 'piece' ? 'btn-primary' : 'btn-outline-secondary'"
                                            @click="selectLineType(li, 'piece')"
                                            title="Piece">
                                        <i class="ti ti-cube"></i>
                                    </button>
                                    <button type="button" class="btn"
                                            :class="line.type === 'box' ? 'btn-primary' : 'btn-outline-secondary'"
                                            @click="selectLineType(li, 'box')"
                                            title="Box">
                                        <i class="ti ti-package"></i>
                                    </button>
                                </div>
                                <input v-if="line.type === 'box'" type="number" min="1"
                                       class="form-control form-control-sm" style="width: 55px;"
                                       v-model.number="line.package_qty"
                                       @change="rebuildRows(li)"
                                       title="Pack Qty — number of inventory rows/products this line creates">
                            </div>
                        </td>

                        {{-- Single-row lines (Pack Qty = 1): hoist row[0]'s
                             inputs straight into the parent row. --}}
                        <template v-if="line.rows.length === 1">
                            <td>
                                <input type="number" min="0" class="form-control form-control-sm"
                                       :class="{ 'is-invalid': rowError(li, 0, 'qty') }"
                                       v-model.number="line.rows[0].qty" placeholder="qty">
                                <div class="invalid-feedback d-block" v-if="rowError(li, 0, 'qty')">@{{ rowError(li, 0, 'qty') }}</div>
                            </td>
                            <td>
                                <input type="number" step="0.001" min="0" class="form-control form-control-sm"
                                       :class="{ 'is-invalid': rowError(li, 0, 'carat_weight') }"
                                       v-model.number="line.rows[0].carat_weight"
                                       placeholder="ct">
                                <div class="invalid-feedback d-block" v-if="rowError(li, 0, 'carat_weight')">@{{ rowError(li, 0, 'carat_weight') }}</div>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm"
                                       v-model="line.rows[0].barcode"
                                       placeholder="optional">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm bg-light" readonly
                                       :placeholder="!form.supplier_id ? 'pick supplier first' : (line.rows[0]._lotCode || '—')"
                                       :title="line.rows[0]._lotCode || ''">
                            </td>
                            {{-- Rack column hidden --}}
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                       :class="{ 'is-invalid': rowError(li, 0, 'price') }"
                                       v-model.number="line.rows[0].price" placeholder="0.00">
                                <div class="invalid-feedback d-block" v-if="rowError(li, 0, 'price')">@{{ rowError(li, 0, 'price') }}</div>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                       v-model.number="line.rows[0].website_price" placeholder="optional">
                            </td>
                            {{-- Tax % and Disc % inputs hidden --}}
                            <td class="text-end fw-semibold">@{{ formatMoney(rowNet(line.rows[0])) }}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-soft-danger" @click="removeLine(li)" title="Remove line">
                                    <i class="ti ti-x"></i>
                                </button>
                            </td>
                        </template>

                        {{-- Multi-row lines (Pack Qty > 1): aggregate
                             readouts + expand/collapse toggle for the child rows. --}}
                        <template v-else>
                            <td colspan="7" class="line-summary-cell" @click="toggleExpand(li)">
                                <div class="d-flex flex-wrap align-items-center gap-3 small">
                                    <i class="ti fs-14 text-muted" :class="line._expanded ? 'ti-chevron-down' : 'ti-chevron-right'"></i>
                                    <span><span class="text-muted">Total pieces:</span>
                                        <strong>@{{ totalPieces(line) }}</strong></span>
                                    <span><span class="text-muted">Inventory rows:</span>
                                        <strong>@{{ line.rows.length }}</strong></span>
                                    <span><span class="text-muted">Line total:</span>
                                        <strong>@{{ formatMoney(lineNet(line)) }}</strong></span>
                                    <span v-if="hasLineError(li)" class="badge badge-soft-danger ms-auto">
                                        <i class="ti ti-alert-circle me-1"></i>Needs attention — click to view
                                    </span>
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-soft-danger" @click="removeLine(li)" title="Remove line">
                                    <i class="ti ti-x"></i>
                                </button>
                            </td>
                        </template>

                    </tr>

                    {{-- ═══════ INLINE CHILD ROWS (multi-row lines, when expanded) ═══════ --}}
                    <template v-if="line.rows.length > 1 && line._expanded">
                        <tr v-for="(row, ri) in line.rows"
                            :key="'l-' + li + '-r-' + ri"
                            class="line-child"
                            :class="{ 'table-active': row._focused, 'line-has-error': rowError(li, ri, 'qty') || rowError(li, ri, 'carat_weight') || rowError(li, ri, 'price') }">

                            <td class="text-muted small bg-light bg-opacity-25"></td>

                            <td class="ps-4 small text-muted bg-light bg-opacity-25">
                                <i class="ti ti-corner-down-right me-1"></i>
                                Pcs #@{{ ri + 1 }}
                                <span v-if="row._product" class="badge badge-soft-success ms-1" :title="row._product.title">
                                    @{{ row._product.sku }}
                                </span>
                                <span v-else class="badge badge-soft-secondary ms-1">New</span>
                                <button type="button"
                                        class="btn btn-icon btn-sm p-0 ms-1 lh-1"
                                        :class="row.website_enabled ? 'text-info' : 'text-muted'"
                                        @click="toggleRowWebsite(row)"
                                        :title="row.website_enabled ? 'Listed on website — click to unlist' : 'Not listed — click to list on website'">
                                    <i class="ti fs-14" :class="row.website_enabled ? 'ti-world' : 'ti-world-off'"></i>
                                </button>
                            </td>

                            <td class="bg-light bg-opacity-25"></td>
                            <td class="bg-light bg-opacity-25"></td>

                            <td>
                                <input type="number" min="0" class="form-control form-control-sm"
                                       :class="{ 'is-invalid': rowError(li, ri, 'qty') }"
                                       v-model.number="row.qty" placeholder="qty">
                                <div class="invalid-feedback d-block" v-if="rowError(li, ri, 'qty')">@{{ rowError(li, ri, 'qty') }}</div>
                            </td>
                            <td>
                                <input type="number" step="0.001" min="0" class="form-control form-control-sm"
                                       :class="{ 'is-invalid': rowError(li, ri, 'carat_weight') }"
                                       v-model.number="row.carat_weight"
                                       placeholder="ct">
                                <div class="invalid-feedback d-block" v-if="rowError(li, ri, 'carat_weight')">@{{ rowError(li, ri, 'carat_weight') }}</div>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm"
                                       :ref="'rowBarcode_' + li + '_' + ri"
                                       v-model="row.barcode"
                                       placeholder="scan/type"
                                       @keydown.enter.prevent="focusNextRow(li, ri)">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm bg-light" readonly
                                       :placeholder="!form.supplier_id ? 'pick supplier first' : (row._lotCode || '—')"
                                       :title="row._lotCode || ''">
                            </td>
                            {{-- Rack column hidden --}}
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                       :class="{ 'is-invalid': rowError(li, ri, 'price') }"
                                       v-model.number="row.price" placeholder="0.00">
                                <div class="invalid-feedback d-block" v-if="rowError(li, ri, 'price')">@{{ rowError(li, ri, 'price') }}</div>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                       v-model.number="row.website_price" placeholder="optional">
                            </td>
                            {{-- Tax % and Disc % inputs hidden --}}
                            <td class="text-end small fw-semibold">@{{ formatMoney(rowNet(row)) }}</td>
                            <td></td>
                        </tr>
                    </template>

                </template>

                <tr v-if="form.lines.length === 0">
                    <td colspan="12" class="text-center text-muted py-4">
                        <i class="ti ti-package fs-22 d-block mb-1 text-muted"></i>
                        <span v-if="!form.supplier_id">Pick a supplier above, then add a row to begin.</span>
                        <template v-else>
                            No items yet.
                            <button type="button" class="btn btn-sm btn-soft-success ms-1" @click="addBlankLine">
                                <i class="ti ti-plus me-1"></i> Add Row
                            </button>
                        </template>
                    </td>
                </tr>

                {{-- A second, always-in-context "Add Row" affordance right
                     under the last line — after entering several rows the
                     header button can be a long scroll away. --}}
                <tr v-else class="add-row-tray">
                    <td colspan="12">
                        <button type="button" class="btn btn-sm btn-soft-success" @click="addBlankLine"
                                :disabled="!form.supplier_id" :title="!form.supplier_id ? 'Pick a supplier first' : ''">
                            <i class="ti ti-plus me-1"></i> Add Another Row
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@push('styles')
<style>
    /* Newly-added-row highlight — a light theme-blue tint instead of
       Bootstrap's default yellow table-warning, so it reads as "new" rather
       than "needs attention". Fades on its own via the existing _highlight
       timeout in the script partial. */
    .purchases-form-page .purchase-line-table tr.line-highlight > td {
        background-color: #eff6ff !important;
        transition: background-color 1.5s ease;
    }

    /* A row (or its collapsed parent) carrying a real validation error —
       a solid left stripe plus a faint red tint draws the eye straight to
       it, instead of leaving the user to scan every field for a stray
       .is-invalid border in a dense, bordered table. */
    .purchases-form-page .purchase-line-table tr.line-has-error > td {
        background-color: #fef2f2 !important;
    }
    .purchases-form-page .purchase-line-table tr.line-has-error > td:first-child {
        box-shadow: inset 3px 0 0 #dc2626;
    }
    .purchases-form-page .purchase-line-table .invalid-feedback {
        font-size: 0.6875rem;
        margin-top: 2px;
    }

    /* Multi-row aggregate/expand bar — was a plain, unstyled clickable
       cell; a hover state and a bit more breathing room make it read as
       an interactive control rather than a static summary line. */
    .purchases-form-page .purchase-line-table .line-summary-cell {
        cursor: pointer;
        padding-top: 10px;
        padding-bottom: 10px;
        transition: background-color .15s ease;
    }
    .purchases-form-page .purchase-line-table .line-summary-cell:hover {
        background-color: #eef2ff !important;
    }

    /* Persistent "Add Another Row" tray under the last line — dashed
       top border sets it apart from real data rows without a heavy box. */
    .purchases-form-page .purchase-line-table tr.add-row-tray > td {
        padding: 10px 12px;
        border-top: 1px dashed #cbd5e1;
        background-color: #fafbfc;
    }

    /* A little extra breathing room on every data-entry cell — the
       original spacing was tuned for maximum density, which made the
       table feel cramped and hard to tap on smaller screens. */
    .purchases-form-page .purchase-line-table > tbody > tr > td {
        padding-top: 6px;
        padding-bottom: 6px;
    }
    .purchases-form-page .purchase-line-table .form-control-sm,
    .purchases-form-page .purchase-line-table .form-select-sm {
        min-height: 32px;
    }

    /* Compact Select2 sizing for the Stone / Country of Origin pickers inside
       the purchase line table — matches the surrounding .form-select-sm cells.
       Pushed from this partial (rather than create/edit.blade.php) so it's
       defined once regardless of which page includes the table. */
    .purchases-form-page .purchase-line-table .select2-container--default .select2-selection--single {
        height: calc(1.5em + 0.5rem + 2px);
        min-height: 31px;
        padding: 0.25rem 0.5rem;
        font-size: 0.8125rem;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        display: flex;
        align-items: center;
    }
    .purchases-form-page .purchase-line-table .select2-container--default .select2-selection--single .select2-selection__rendered {
        padding: 0;
        line-height: normal;
    }
    .purchases-form-page .purchase-line-table .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100%;
        top: 0;
        right: 4px;
    }
    .purchases-form-page .purchase-line-table .select2-container--default .select2-selection--single .select2-selection__clear {
        margin-right: 4px;
    }
    /* Select2 portals its open dropdown panel to <body>, so it can't be scoped
       under .purchases-form-page — defined once here (rather than per-page)
       since it applies globally to every Select2 instance in this module,
       and is currently the only place in the app using Select2. */
    .select2-dropdown {
        font-size: 0.8125rem;
    }
</style>
@endpush
