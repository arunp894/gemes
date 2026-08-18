{{-- Inline multi-row product table.

     Layout strategy (driven by `line.rows.length`, not `line.type`):
       - Each LINE renders a "parent" <tr> with the item's title/category,
         type pill, and Pack Qty input. Pack Qty drives inventory-row
         count for BOX lines only (rebuildRows() keeps line.rows in
         sync) — each row becomes its own product on save. PIECE lines
         are always exactly one row; Pack Qty is disabled for them and
         the physical count goes on that row's own Qty field instead, so
         several identical pieces can share one product.
       - When a line has exactly ONE row, the parent row also carries that
         row's qty / carat / barcode / rack / price inputs inline.
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
        <span class="badge bg-soft-primary text-primary ms-2">@{{ form.lines.length }} lines</span>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0 purchase-line-table">
            <thead class="bg-light bg-opacity-25 text-uppercase fs-xxs">
                <tr>
                    <th style="width: 32px;">#</th>
                    <th>Item</th>
                    <th style="width: 110px;">Type</th>
                    <th style="width: 110px;">Pack Qty</th>
                    <th style="width: 90px;">Qty</th>
                    <th style="width: 100px;">Carat</th>
                    <th style="width: 170px;">Barcode</th>
                    <th style="width: 130px;">Lot Code</th>
                    {{-- Rack column hidden --}}
                    <th style="width: 110px;">Price</th>
                    <th style="width: 110px;">Selling Price</th>
                    {{-- Tax % and Disc % hidden --}}
                    <th style="width: 110px;" class="text-end">Line Total</th>
                    <th style="width: 40px;"></th>
                </tr>
            </thead>

            <tbody>
                <template v-for="(line, li) in form.lines">

                    {{-- ═══════ PARENT ROW (always rendered) ═══════ --}}
                    <tr :key="'l-' + li" class="line-parent" :class="{ 'table-warning': line._highlight }">

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
                                <div>
                                    <div class="fw-semibold">@{{ line.title }}</div>
                                    <small class="text-muted">@{{ categoryName(line.category_id) }}</small>
                                </div>
                                <button type="button"
                                        class="btn btn-icon btn-sm flex-shrink-0"
                                        :class="line.website_enabled ? 'btn-soft-info' : 'btn-default'"
                                        @click="line.website_enabled = !line.website_enabled"
                                        :title="line.website_enabled ? 'Listed on website — click to unlist' : 'Not listed — click to list on website'">
                                    <i class="ti fs-16" :class="line.website_enabled ? 'ti-world' : 'ti-world-off'"></i>
                                </button>
                            </div>
                        </td>

<td>
    <select class="form-select form-select-sm"
            v-model="line.type"
            @change="rebuildRows(li)">
        <option value="piece">Piece</option>
        <option value="box">Box</option>
    </select>
</td>

                        {{-- Pack Qty: number of inventory rows/products this line
                             produces. Disabled for Piece — a Piece line is always
                             exactly one row; the physical count goes on that row's
                             own Qty field instead. --}}
                        <td>
                            <div class="input-group input-group-sm">
                                <input type="number" min="1" class="form-control"
                                       v-model.number="line.package_qty"
                                       :disabled="line.type === 'piece'"
                                       @change="rebuildRows(li)"
                                       @keydown.enter.prevent="focusFirstRow(li)">
                                <span class="input-group-text">
                                    @{{ line.type === 'piece' ? 'pcs' : 'box' }}
                                </span>
                            </div>
                        </td>

                        {{-- Single-row lines (Pack Qty = 1): hoist row[0]'s
                             inputs straight into the parent row. --}}
                        <template v-if="line.rows.length === 1">
                            <td>
                                <input type="number" min="0" class="form-control form-control-sm"
                                       v-model.number="line.rows[0].qty">
                            </td>
                            <td>
                                <input type="number" step="0.001" min="0" class="form-control form-control-sm"
                                       v-model.number="line.rows[0].carat_weight"
                                       placeholder="ct">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm"
                                       v-model="line.rows[0].barcode"
                                       placeholder="optional">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm bg-light" readonly
                                       :placeholder="!form.supplier_id ? 'pick supplier first' : (line.rows[0]._lotCode || '—')">
                            </td>
                            {{-- Rack column hidden --}}
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                       v-model.number="line.rows[0].price">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                       v-model.number="line.rows[0].website_price" placeholder="optional">
                            </td>
                            {{-- Tax % and Disc % inputs hidden --}}
                            <td class="text-end fw-semibold">@{{ formatMoney(rowNet(line.rows[0])) }}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-soft-danger" @click="removeLine(li)">
                                    <i class="ti ti-x"></i>
                                </button>
                            </td>
                        </template>

                        {{-- Multi-row lines (Pack Qty > 1): aggregate
                             readouts + expand/collapse toggle for the child rows. --}}
                        <template v-else>
                            <td colspan="7" class="bg-light bg-opacity-25"
    style="cursor: pointer;"
    @click="toggleExpand(li)">
                                <div class="d-flex flex-wrap gap-3 small">
                                    <span><span class="text-muted">Total pieces:</span>
                                        <strong>@{{ totalPieces(line) }}</strong></span>
                                    <span><span class="text-muted">Inventory rows:</span>
                                        <strong>@{{ line.rows.length }}</strong></span>
                                    <span><span class="text-muted">Line total:</span>
                                        <strong>@{{ formatMoney(lineNet(line)) }}</strong></span>
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-soft-danger" @click="removeLine(li)">
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
                            :class="{ 'table-active': row._focused }">

                            <td class="text-muted small bg-light bg-opacity-25"></td>

                            <td class="ps-4 small text-muted bg-light bg-opacity-25">
                                <i class="ti ti-corner-down-right me-1"></i>
                                @{{ line.package_name }} #@{{ ri + 1 }}
                                <span v-if="row._product" class="badge badge-soft-success ms-1" :title="row._product.title">
                                    @{{ row._product.sku }}
                                </span>
                                <span v-else class="badge badge-soft-secondary ms-1">New</span>
                            </td>

                            <td class="bg-light bg-opacity-25"></td>
                            <td class="bg-light bg-opacity-25"></td>

                            <td>
                                <input type="number" min="0" class="form-control form-control-sm"
                                       v-model.number="row.qty">
                            </td>
                            <td>
                                <input type="number" step="0.001" min="0" class="form-control form-control-sm"
                                       v-model.number="row.carat_weight"
                                       placeholder="ct">
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
                                       :placeholder="!form.supplier_id ? 'pick supplier first' : (row._lotCode || '—')">
                            </td>
                            {{-- Rack column hidden --}}
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                       v-model.number="row.price">
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
                        Add an item above to begin.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
