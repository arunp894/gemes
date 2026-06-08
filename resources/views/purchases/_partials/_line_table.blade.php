{{-- Inline multi-row product table.

     Layout strategy:
       - Each LINE renders a "parent" <tr> with the product name, type pill,
         and outer package qty.
       - For piece-type products, the parent row also carries the single
         qty / price inputs (one inline row total).
       - For unit/carton products, the parent row's qty cell shows the
         outer-pack qty input ("2 Cartons"), and CHILD <tr>s are emitted
         directly below the parent INSIDE THE SAME <tbody>, one per inner
         inventory unit (e.g. one per Box). Child rows use a faint left
         indent so the hierarchy is obvious without nested tables.
--}}

<div class="card">
    <div class="card-header border-light d-flex align-items-center gap-2">
        <i class="ti ti-list-details fs-18 text-primary"></i>
        <h5 class="card-title mb-0">Purchase Lines</h5>
        <span class="badge bg-soft-primary text-primary ms-2">@{{ form.lines.length }} lines</span>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0 purchase-line-table">
            <thead class="bg-light bg-opacity-25 text-uppercase fs-xxs">
                <tr>
                    <th style="width: 32px;">#</th>
                    <th>Product</th>
                    <th style="width: 110px;">Type</th>
                    <th style="width: 110px;">Pack Qty</th>
                    <th style="width: 90px;">Qty</th>
                    <th style="width: 100px;">Carat</th>
                    <th style="width: 170px;">Barcode</th>
                    <th style="width: 130px;">Rack</th>
                    <th style="width: 110px;">Price</th>
                    {{-- Tax % and Disc % hidden --}}
                    {{-- <th v-if="false" style="width: 130px;">Expiry</th> --}}
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
        <button v-if="line.type === 'box'" type="button"
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
                            <div class="fw-semibold">@{{ line._product.title }}</div>
                            <small class="text-muted">SKU: @{{ line._product.sku }}</small>
                        </td>

<td>
    <select class="form-select form-select-sm"
            v-model="line.type"
            @change="rebuildRows(li)">
        <option value="piece">Piece</option>
        <option value="box">Box</option>
    </select>
    <div v-if="line.type === 'box'" class="small text-muted mt-1">
        1 Box = @{{ line._product.packaging.inner_pack_contains || 1 }} pcs
    </div>
</td>

                        {{-- Pack Qty: how many outermost packs (cartons / units / pieces) --}}
                        <td>
                            <div class="input-group input-group-sm">
                                <input type="number" min="1" class="form-control"
                                       v-model.number="line.package_qty"
                                       @change="rebuildRows(li)"
                                       @keydown.enter.prevent="focusFirstRow(li)">
                                <span class="input-group-text" v-if="line.type !== 'piece'">
                                    @{{ line._product.packaging.outer_pack_name || 'ctn' }}
                                </span>
                            </div>
                        </td>

                        {{-- For piece-type products, the parent row IS the only row.
                             We hoist row[0]'s inputs straight into the parent. --}}
                        <template v-if="line.type === 'piece' && line._expanded">
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
                                <select class="form-select form-select-sm" v-model.number="line.rows[0].rack_id">
                                    <option :value="null">—</option>
                                    <option v-for="r in racks" :key="r.id" :value="r.id">
                                        @{{ r.code }}
                                    </option>
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                       v-model.number="line.rows[0].price">
                            </td>
                            {{-- Tax % and Disc % inputs hidden --}}
                            {{-- <td v-if="false">
                                <input type="date" class="form-control form-control-sm"
                                       v-model="line.rows[0].expiry_date">
                            </td> --}}
                            <td class="text-end fw-semibold">@{{ formatMoney(rowNet(line.rows[0])) }}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-soft-danger" @click="removeLine(li)">
                                    <i class="ti ti-x"></i>
                                </button>
                            </td>
                        </template>

                        {{-- For carton/unit, the parent row shows aggregate readouts. --}}
                        <template v-else>
                            <td colspan="9" class="bg-light bg-opacity-25" 
    :style="line.type === 'box' ? 'cursor: pointer;' : ''"
    @click="line.type === 'box' && toggleExpand(li)">
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

                    {{-- ═══════ INLINE CHILD ROWS (only for carton/unit) ═══════ --}}
                    <template v-if="line.type !== 'piece'">
                        <tr v-for="(row, ri) in line.rows"
                            :key="'l-' + li + '-r-' + ri"
                            class="line-child"
                            :class="{ 'table-active': row._focused }">

                            <td class="text-muted small bg-light bg-opacity-25"></td>

                            <td class="ps-4 small text-muted bg-light bg-opacity-25">
                                <i class="ti ti-corner-down-right me-1"></i>
                                @{{ line._product.packaging.inner_pack_name || 'Box' }} #@{{ ri + 1 }}
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
                                <select class="form-select form-select-sm" v-model.number="row.rack_id">
                                    <option :value="null">—</option>
                                    <option v-for="r in racks" :key="r.id" :value="r.id">
                                        @{{ r.code }}
                                    </option>
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                       v-model.number="row.price">
                            </td>
                            {{-- Tax % and Disc % inputs hidden --}}
                            {{-- <td>
                                <input type="date" class="form-control form-control-sm"
                                       v-model="row.expiry_date">
                            </td> --}}
                            <td class="text-end small fw-semibold">@{{ formatMoney(rowNet(row)) }}</td>
                            <td></td>
                        </tr>
                    </template>

                </template>

                <tr v-if="form.lines.length === 0">
                    <td colspan="14" class="text-center text-muted py-4">
                        <i class="ti ti-barcode fs-22 d-block mb-1 text-muted"></i>
                        Scan a barcode or search for a product to begin.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
