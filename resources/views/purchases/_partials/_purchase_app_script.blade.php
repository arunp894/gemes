{{-- Vue 2 application for the purchase create/edit form.

     Mounting:
       new Vue({ el: '#purchaseFormApp', data, methods })

     As of the "purchase creates its own products" change, a line no
     longer points at an existing catalogue product — it's a template
     (category, title) that gets stamped onto a brand-new Product for
     every inventory row generated under it. Lines are added and edited
     directly in the Purchase Items table (addBlankLine() pushes a blank
     one; its Stone/Type/Country/etc. cells are then filled in inline) —
     there's no separate add-item form. Gemstone-specific grading fields
     (stone type, treatment, cut, clarity, colour, description) aren't
     collected here at all; they're set later by editing the individual
     Product once it exists, same as photos already are.

     Configuration (interpolated via Blade above the script):
       mode              create|edit
       suppliersJson     JSON of supplier list   (id, name, company_name, supplier_code, invoice_prefix, gst_number)
       locationsJson     JSON of location list
       racksJson         JSON of rack list       (id, code, name)
       categoriesJson    JSON of category list   (id, name, code, is_gemstone)
       countriesOfOriginJson JSON of country-of-origin list (id, name)
       previewUrl        GET endpoint: supplier_id, date -> next invoice number
       lotCodePreviewUrl GET endpoint: supplier_id, category_id, count -> next lot code(s)
       submitUrl         POST/PUT endpoint for save
       submitMethod      POST or PUT
       existingPurchase  null on create, hydrated Purchase model on edit
--}}
<script>
(function () {
    // ── Configuration shipped from Blade ───────────────────────────
    const CONFIG = {
        mode:        @json($mode),
        currencySymbol: @json($currencySymbol),
        suppliers:   {!! $suppliersJson !!},
        locations:   {!! $locationsJson !!},
        racks:       {!! $racksJson !!},
        categories:  {!! $categoriesJson !!},
        countriesOfOrigin: {!! $countriesOfOriginJson !!},
        previewUrl:  @json($previewUrl),
        lotCodePreviewUrl: @json($lotCodePreviewUrl),
        // Route template for the supplier -> categories AJAX lookup. The
        // literal token is swapped for the real supplier id at call time
        // (see fetchSupplierCategories()) so this stays a normal named
        // route rather than a hand-built URL.
        supplierCategoriesUrlTemplate: @json(route('suppliers.categories', ['supplier' => '__SUPPLIER_ID__'])),
        submitUrl:   @json($submitUrl),
        submitMethod:@json($submitMethod),
        existing:    {!! $existingPurchase ? $existingPurchase->toJson() : 'null' !!},
    };

    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    // ── Helpers ─────────────────────────────────────────────────────
    function emptyRow(caratDefault, websitePriceDefault, barcodeDefault, priceDefault, websiteEnabledDefault, qtyDefault) {
        return {
            id:               null,
            qty:              (qtyDefault !== undefined && qtyDefault !== null) ? qtyDefault : 1,
            carat_weight:     (caratDefault !== undefined && caratDefault !== null) ? caratDefault : null,
            barcode:          (barcodeDefault !== undefined && barcodeDefault !== null && barcodeDefault !== '') ? barcodeDefault : '',
            rack_id:          null,
            serial_number:    null,
            price:            (priceDefault !== undefined && priceDefault !== null && priceDefault !== '') ? priceDefault : 0,
            website_price:    (websitePriceDefault !== undefined && websitePriceDefault !== null) ? websitePriceDefault : null,
            website_enabled:  !!websiteEnabledDefault,
            tax_percent:      0,
            discount_percent: 0,
            expiry_date:      null,
            manufacture_date: null,
            remarks:          null,
            _focused:         false,
            _lotCode:         null,
            _product:         null, // populated only for rows hydrated from an existing purchase
        };
    }

    // A brand-new line added straight into the Purchase Items table —
    // Stone/Type/Country/etc. all start blank and are filled in inline
    // (see addBlankLine()). Defaults to Piece, the more common case;
    // gemstone grading fields are never collected here (see file header).
    function emptyLine() {
        return {
            id:                 null,
            category_id:        null,
            title:              '',
            short_description:  null,
            full_description:   null,
            country_of_origin_id: null,
            notes_tags:         null,
            website_price:      null,
            website_enabled:    false,
            carat_weight:       null,
            stone_type:         null,
            colour_grade:       null,
            clarity_grade:      null,
            cut_shape:          null,
            treatment:          null,
            stone_description:  null,
            _highlight:         true,
            _expanded:          false,
            type:               'piece',
            package_name:       'Piece',
            package_qty:        1,
            remarks:            null,
            rows:               [emptyRow()],
        };
    }

    // ── Hydrate from an existing purchase (edit mode) ───────────────
    function hydrateLines(purchase) {
        if (!purchase || !Array.isArray(purchase.lines)) return [];

        return purchase.lines.map(l => ({
            id:                 l.id,
            category_id:        l.category_id,
            title:              l.title || '',
            short_description:  l.short_description,
            full_description:   l.full_description,
            country_of_origin_id: l.country_of_origin_id || null,
            notes_tags:         l.notes_tags,
            website_price:      (l.website_price !== null && l.website_price !== undefined) ? parseFloat(l.website_price) : null,
            website_enabled:    !!l.website_enabled,
            carat_weight:       (l.carat_weight !== null && l.carat_weight !== undefined) ? parseFloat(l.carat_weight) : null,
            stone_type:         l.stone_type,
            colour_grade:       l.colour_grade || '',
            clarity_grade:      l.clarity_grade,
            cut_shape:          l.cut_shape,
            treatment:          l.treatment,
            stone_description:  l.stone_description || '',
            _highlight:         false,
            _expanded:          (l.rows || []).length > 1,
            type:               l.type,
            package_name:       l.package_name,
            package_qty:        l.package_qty,
            remarks:            l.remarks,
            rows: (l.rows || []).map(r => ({
                id:               r.id,
                qty:              r.qty,
                carat_weight:     (r.carat_weight !== null && r.carat_weight !== undefined) ? parseFloat(r.carat_weight) : null,
                barcode:          r.barcode || '',
                rack_id:          r.rack_id,
                serial_number:    r.serial_number,
                price:            parseFloat(r.price)            || 0,
                website_price:    (r.website_price !== null && r.website_price !== undefined) ? parseFloat(r.website_price) : null,
                website_enabled:  !!r.website_enabled,
                tax_percent:      parseFloat(r.tax_percent)      || 0,
                discount_percent: parseFloat(r.discount_percent) || 0,
                expiry_date:      r.expiry_date,
                manufacture_date: r.manufacture_date,
                remarks:          r.remarks,
                _focused:         false,
                _lotCode:         r.lot_code || null,
                _product:         r.product ? { id: r.product.id, title: r.product.title, sku: r.product.sku } : null,
            })),
        }));
    }

    // ── Searchable-select directive ─────────────────────────────────
    // Wraps a plain <select v-model="..."> with Select2 for search-as-you-type.
    // Deliberately does NOT write to the Vue model itself — it just makes
    // Select2 dispatch a genuine native 'change' event on the underlying
    // <select> when the user picks/clears a value, which is exactly what a
    // native dropdown interaction does, so the element's own existing
    // v-model / @change bindings keep working completely unchanged. This
    // also means it's safe to reuse on any <select v-model> in this form
    // (supplier, and the per-row Stone / Country of Origin pickers, which
    // are created and destroyed dynamically as lines are added/removed).
    Vue.directive('select2', {
        inserted(el) {
            const $el = $(el);
            $el.select2({
                width: '100%',
                placeholder: el.dataset.placeholder || 'Search…',
                allowClear: true,
            }).on('select2:select select2:clear', function () {
                el.dispatchEvent(new Event('change', { bubbles: true }));
            });
            el.dataset.select2LastValue = $el.val() || '';
        },
        componentUpdated(el) {
            // v-for rows are keyed by index ('l-' + li), so removing a line
            // from the middle of the list (removeLine() -> splice) shifts
            // every later line down a position — Vue then reuses that DOM
            // node in place for the shifted line's data instead of
            // recreating it, which changes the underlying <select>'s value
            // without the user ever touching this particular widget.
            // Select2 caches its own displayed label, so it needs to be
            // told to re-read the value — but only when it actually
            // changed underneath it; this hook fires on every reactive
            // update anywhere in the form, so an unconditional refresh
            // here would be needlessly expensive.
            const $el = $(el);
            const current = $el.val() || '';
            if (current !== el.dataset.select2LastValue) {
                el.dataset.select2LastValue = current;
                // Namespaced trigger: reaches only Select2's own internal
                // listener (bound as 'change.select2'), not the element's
                // native v-model/@change listener — Vue's model is already
                // correct here (it drove this very value change), only
                // Select2's cached display needs to catch up.
                $el.trigger('change.select2');
            }
        },
        unbind(el) {
            const $el = $(el);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
        },
    });

    // ── Vue instance ────────────────────────────────────────────────
    new Vue({
        el: '#purchaseFormApp',
        data: {
            suppliers:      CONFIG.suppliers,
            locations:      CONFIG.locations,
            racks:          CONFIG.racks,
            categories:     CONFIG.categories,
            countriesOfOrigin: CONFIG.countriesOfOrigin,
            // Subset of `categories` mapped to the chosen supplier (see
            // fetchSupplierCategories()). Empty until a supplier with at
            // least one mapped category is selected; the Stone dropdown on
            // each new line falls back to the full `categories` list
            // otherwise (see the categoryOptions computed) so unmapped
            // suppliers still work.
            supplierCategories:       [],
            supplierCategoriesLoaded: false,
            formMessage:    '',
            formLevel:      '',   // 'success' | 'danger' | 'info'
            submitting:     false,
            wasValidated:   false,
            errors:         {},

            form: {
                supplier_id:            CONFIG.existing ? CONFIG.existing.supplier_id : null,
                location_id:            CONFIG.existing ? (CONFIG.existing.location_id || null) : null,
                purchase_date:          CONFIG.existing ? CONFIG.existing.purchase_date : new Date().toISOString().slice(0, 10),
                invoice_number_preview: CONFIG.existing ? CONFIG.existing.invoice_number : '',
                tax_type:               CONFIG.existing ? CONFIG.existing.tax_type : 'none',
                note:                   CONFIG.existing ? CONFIG.existing.note : '',
                // Payments entered here are only submitted on create —
                // editing an existing purchase never touches its payments
                // (managed from the purchase's detail page instead).
                payments:               [],
                lines:                  CONFIG.existing ? hydrateLines(CONFIG.existing) : [],
            },
        },

        computed: {
            // Edit mode: payments aren't editable from this form, so this
            // reflects the purchase's last-known paid amount (managed from
            // the detail page). Create mode: live sum of the payments
            // being entered below.
            paidAmount() {
                if (CONFIG.existing) {
                    return parseFloat(CONFIG.existing.paid_amount) || 0;
                }
                return this.form.payments.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0);
            },
            totals() {
                let subtotal = 0;

                this.form.lines.forEach(line => {
                    line.rows.forEach(r => {
                        const carat = parseFloat(r.carat_weight) || 0;
                        const price = parseFloat(r.price) || 0;
                        subtotal += carat * price;
                    });
                });

                const grand = subtotal;
                const paid  = this.paidAmount;
                const due   = Math.max(0, grand - paid);

                return { subtotal, discount: 0, tax: 0, grand, paid, due };
            },
            totalRows() {
                return this.form.lines.reduce((acc, l) => acc + l.rows.length, 0);
            },
            totalPiecesAll() {
                return this.form.lines.reduce((acc, l) => acc + this.totalPieces(l), 0);
            },
            // Categories offered in each line's Stone dropdown: the
            // supplier-mapped subset once loaded and non-empty, otherwise
            // every active category (covers suppliers with no mapping yet
            // and the brief window before a supplier is chosen).
            categoryOptions() {
                return this.supplierCategories.length ? this.supplierCategories : this.categories;
            },
            formAlertClass() {
                return {
                    'alert-success': this.formLevel === 'success',
                    'alert-danger':  this.formLevel === 'danger',
                    'alert-info':    this.formLevel === 'info' || !this.formLevel,
                };
            },
            formIconClass() {
                return {
                    'ti me-1':         true,
                    'ti-circle-check': this.formLevel === 'success',
                    'ti-alert-circle': this.formLevel === 'danger',
                    'ti-info-circle':  this.formLevel === 'info' || !this.formLevel,
                };
            },
        },

        mounted() {
            if (this.form.supplier_id && !CONFIG.existing) {
                this.refreshInvoiceNumber();
            }
            // Edit mode locks the supplier, and a fresh create can arrive
            // with one preselected — either way, load its mapped
            // categories up front rather than waiting for a change event
            // that may never fire.
            if (this.form.supplier_id) {
                this.fetchSupplierCategories();
            }
        },

        methods: {

            /* ─── Toasts ────────────────────────────────────────── */

            showToast(type, message) {
                const container = document.getElementById('purchaseToastContainer');
                if (!container) return;
                const isSuccess = type === 'success';
                const el = document.createElement('div');
                el.className = 'toast align-items-center border-0 text-bg-' + (isSuccess ? 'success' : 'danger');
                el.setAttribute('role', 'alert');
                el.setAttribute('aria-live', 'assertive');
                el.setAttribute('aria-atomic', 'true');
                el.innerHTML = '<div class="d-flex">'
                    + '<div class="toast-body d-flex align-items-center gap-2">'
                    + '<i class="ti ' + (isSuccess ? 'ti-circle-check' : 'ti-alert-circle') + ' fs-lg"></i>'
                    + $('<div/>').text(message).html()
                    + '</div>'
                    + '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>'
                    + '</div>';
                container.appendChild(el);
                const toast = new bootstrap.Toast(el, { delay: 2500 });
                el.addEventListener('hidden.bs.toast', () => el.remove());
                toast.show();
            },

            /* ─── Row actions ───────────────────────────────────── */

            toggleRowWebsite(row) {
                row.website_enabled = !row.website_enabled;
                this.showToast('success', row.website_enabled ? 'Listed on website.' : 'Removed from website.');
            },

            /* ─── Money / row math ──────────────────────────────── */

            formatMoney(v) {
                const n = parseFloat(v) || 0;
                return CONFIG.currencySymbol + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            rowNet(r) {
                const carat = parseFloat(r.carat_weight) || 0;
                const price = parseFloat(r.price) || 0;
                return carat * price;
            },
            lineNet(line) {
                return line.rows.reduce((acc, r) => acc + this.rowNet(r), 0);
            },
            totalPieces(line) {
                return line.rows.reduce((acc, r) => acc + (parseInt(r.qty, 10) || 0), 0);
            },
            categoryName(id) {
                const c = this.categories.find(c => c.id === id);
                return c ? c.name : '—';
            },
            countryOfOriginName(id) {
                const o = this.countriesOfOrigin.find(o => o.id === id);
                return o ? o.name : '—';
            },

            /* Payments (create mode only) */

            addPayment() {
                this.form.payments.push({
                    payment_date:     this.form.purchase_date,
                    amount:           +Math.max(0, this.totals.due).toFixed(2),
                    payment_method:   'cash',
                    reference_number: '',
                });
            },
            removePayment(idx) {
                this.form.payments.splice(idx, 1);
            },

            /* ─── Supplier / invoice number ──────────────────────── */

            onSupplierChange() {
                this.refreshInvoiceNumber();
                this.fetchSupplierCategories();
                // Every existing preview embeds the OLD supplier's initials
                // (create mode only — supplier is locked/readonly on edit),
                // so clear them all before refetching rather than only
                // filling gaps.
                this.form.lines.forEach(line => {
                    line.rows.forEach(row => { row._lotCode = null; });
                });
                this.refreshAllLotCodePreviews();
            },

            /* ─── Supplier -> mapped categories ───────────────────────
               Filters each line's Stone dropdown down to whatever the
               chosen supplier is mapped to (Supplier::categories()). A
               supplier with no mapping yet resolves to an empty list here,
               and categoryOptions() falls back to the full category list
               in that case — see its comment. */
            fetchSupplierCategories() {
                this.supplierCategories = [];
                this.supplierCategoriesLoaded = false;
                if (!this.form.supplier_id) return;

                const url = CONFIG.supplierCategoriesUrlTemplate.replace('__SUPPLIER_ID__', this.form.supplier_id);
                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(j => {
                        if (!j.success) return;
                        this.supplierCategories = j.categories || [];
                        this.supplierCategoriesLoaded = true;
                    })
                    .catch(() => {});
            },
            refreshInvoiceNumber() {
                if (!this.form.supplier_id || !this.form.purchase_date) return;
                const url = `${CONFIG.previewUrl}?supplier_id=${this.form.supplier_id}&date=${this.form.purchase_date}`;
                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(j => {
                        if (j.ok) this.form.invoice_number_preview = j.invoice_number;
                    })
                    .catch(() => {});
            },

            /* ─── Lot code preview ────────────────────────────
               Only fills rows that don't already have a code — real
               (hydrated) codes from an existing purchase are left alone;
               only newly added rows get a live preview. Keyed on the
               line's category now, not a pre-existing product. */
            refreshLotCodePreview(lineIdx) {
                const line = this.form.lines[lineIdx];
                if (!line || !this.form.supplier_id || !line.category_id) return;

                const missing = [];
                line.rows.forEach((row, ri) => { if (!row._lotCode) missing.push(ri); });
                if (missing.length === 0) return;

                const url = `${CONFIG.lotCodePreviewUrl}?supplier_id=${this.form.supplier_id}`
                    + `&category_id=${line.category_id}&count=${missing.length}`;
                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(j => {
                        if (!j.ok || !Array.isArray(j.codes)) return;
                        missing.forEach((ri, idx) => {
                            if (line.rows[ri]) this.$set(line.rows[ri], '_lotCode', j.codes[idx] || null);
                        });
                    })
                    .catch(() => {});
            },
            refreshAllLotCodePreviews() {
                this.form.lines.forEach((_, li) => this.refreshLotCodePreview(li));
            },

            /* ─── Messages ───────────────────────────────────────── */

            setMessage(level, msg) {
                this.formLevel   = level;
                this.formMessage = msg;
                if (level === 'success' || level === 'info') {
                    setTimeout(() => { if (this.formMessage === msg) this.formMessage = ''; }, 3000);
                }
            },
            flashLine(li) {
                this.$set(this.form.lines[li], '_highlight', true);
                setTimeout(() => {
                    if (this.form.lines[li]) this.$set(this.form.lines[li], '_highlight', false);
                }, 1500);
            },
            toggleExpand(li) {
                const line = this.form.lines[li];
                this.$set(line, '_expanded', !line._expanded);
            },

            /* ─── Line management ────────────────────────────────── */

            // Pushes a blank line straight into the Purchase Items table —
            // its Stone/Type/Country/etc. cells render inline-editable
            // immediately (see _line_table.blade.php's `!line.id` branch).
            addBlankLine() {
                this.form.lines.push(emptyLine());
                this.flashLine(this.form.lines.length - 1);
            },

            // Fired when a new (unsaved) line's Stone dropdown changes —
            // keeps the line's title in sync with its category and kicks
            // off a fresh lot code preview now that one can be computed.
            onLineCategoryChange(li) {
                const line = this.form.lines[li];
                line.title = this.categoryName(line.category_id);
                line.rows.forEach(row => { row._lotCode = null; });
                this.refreshLotCodePreview(li);
            },

            /* ─── Row management ─────────────────────────────────── */

            selectLineType(li, type) {
                const line = this.form.lines[li];
                if (line.type === type) return;
                line.type = type;

                // Switching TO Box: whatever Pcs/Carat/Price/Selling Price
                // were entered for a single Piece don't carry meaning once
                // this line fans out into one row per box, so start clean
                // rather than silently carrying stale values forward.
                if (type === 'box') {
                    line.rows.forEach(row => {
                        this.$set(row, 'qty', 0);
                        this.$set(row, 'carat_weight', 0);
                        this.$set(row, 'price', 0);
                        this.$set(row, 'website_price', 0);
                    });
                }

                this.rebuildRows(li);
            },

            rebuildRows(lineIdx) {
                const line = this.form.lines[lineIdx];
                line.package_name = line.type === 'piece' ? 'Piece' : 'Box';

                // Piece lines are always exactly one inventory row/product
                // — Pack Qty isn't meaningful for them (disabled in the UI);
                // the physical count lives on that row's own Qty field
                // instead, so several identical pieces can share one
                // product. Only Box lines fan out into one row per box.
                if (line.type === 'piece') {
                    line.package_qty = 1;
                } else {
                    line.package_qty = Math.max(1, parseInt(line.package_qty, 10) || 1);
                }

                const expected = (line.type === 'piece') ? 1 : line.package_qty;

                if (line.rows.length < expected) {
                    while (line.rows.length < expected) {
                        const t = line.rows[line.rows.length - 1];

                        // Box lines: every fanned-out row is its own distinct
                        // physical unit, not a duplicate of the last one, so
                        // it always starts at 0 — never copied from whatever
                        // the previous row currently holds (that previous
                        // row's own value may itself be stale/mid-edit).
                        const row = (line.type === 'box')
                            ? emptyRow(0, 0, undefined, 0, t ? t.website_enabled : line.website_enabled, 0)
                            : emptyRow(
                                t ? t.carat_weight : line.carat_weight,
                                t ? t.website_price : line.website_price,
                                undefined,
                                undefined,
                                t ? t.website_enabled : line.website_enabled
                              );

                        if (t) {
                            row.rack_id     = t.rack_id;
                            row.expiry_date = t.expiry_date;
                            if (line.type !== 'box') {
                                row.price = t.price;
                            }
                        }
                        line.rows.push(row);
                    }
                } else if (line.rows.length > expected) {
                    const removed = line.rows.slice(expected);
                    const locked  = removed.filter(r => r._product);
                    if (locked.length && !confirm(
                        `${locked.length} row(s) you're about to drop already have a product created. `
                        + `Continue? (Rows with photos, extra barcodes, or a website listing will be rejected on save.)`
                    )) {
                        line.package_qty = line.rows.length; // revert
                        return;
                    }
                    line.rows.splice(expected);
                }

                if (line.rows.length > 1) line._expanded = true;
                this.refreshLotCodePreview(lineIdx);
            },
            focusFirstRow(li) {
                const refKey = `rowBarcode_${li}_0`;
                this.$nextTick(() => {
                    const el = this.$refs[refKey];
                    const target = Array.isArray(el) ? el[0] : el;
                    target?.focus();
                });
            },
            focusNextRow(li, ri) {
                const line = this.form.lines[li];
                if (ri + 1 < line.rows.length) {
                    const refKey = `rowBarcode_${li}_${ri + 1}`;
                    this.$nextTick(() => {
                        const el = this.$refs[refKey];
                        const target = Array.isArray(el) ? el[0] : el;
                        target?.focus();
                    });
                }
            },
            removeLine(li) {
                const line = this.form.lines[li];
                const lockedCount = line.rows.filter(r => r._product).length;
                const msg = lockedCount
                    ? `Remove "${line.title}"? ${lockedCount} of its item(s) already have a product created — `
                      + `rows with photos, extra barcodes, or a website listing will be rejected on save.`
                    : `Remove "${line.title}" from this purchase?`;
                if (!confirm(msg)) return;
                this.form.lines.splice(li, 1);
            },

            /* ─── Submit ─────────────────────────────────────────── */

            buildPayload(post) {
                const payload = {
                    supplier_id:   this.form.supplier_id,
                    location_id:   this.form.location_id,
                    purchase_date: this.form.purchase_date,
                    tax_type:      this.form.tax_type,
                    note:          this.form.note,
                    status:        post ? 'posted' : 'draft',
                    lines: this.form.lines.map(l => ({
                        id:                 l.id,
                        category_id:        l.category_id,
                        // Title has no dedicated input anymore — it's
                        // always just the category name.
                        title:              this.categoryName(l.category_id),
                        short_description:  l.short_description,
                        full_description:   l.full_description,
                        country_of_origin_id: l.country_of_origin_id,
                        notes_tags:         l.notes_tags,
                        website_price:      (l.website_price === '' || l.website_price === undefined) ? null : l.website_price,
                        website_enabled:    !!l.website_enabled,
                        carat_weight:       (l.carat_weight === '' || l.carat_weight === undefined) ? null : l.carat_weight,
                        stone_type:         l.stone_type,
                        colour_grade:       l.colour_grade,
                        clarity_grade:      l.clarity_grade,
                        cut_shape:          l.cut_shape,
                        treatment:          l.treatment,
                        stone_description:  l.stone_description,
                        type:               l.type,
                        package_name:       l.package_name,
                        package_qty:        l.package_qty,
                        remarks:            l.remarks,
                        rows: l.rows.map(r => ({
                            id:               r.id,
                            qty:              r.qty,
                            carat_weight:     (r.carat_weight === '' || r.carat_weight === undefined) ? null : r.carat_weight,
                            barcode:          r.barcode || null,
                            rack_id:          r.rack_id,
                            serial_number:    r.serial_number,
                            price:            r.price,
                            website_price:    (r.website_price === '' || r.website_price === undefined) ? null : r.website_price,
                            website_enabled:  !!r.website_enabled,
                            expiry_date:      r.expiry_date,
                            manufacture_date: r.manufacture_date,
                            remarks:          r.remarks,
                        })),
                    })),
                };

                // Payments are only submitted on create — editing an
                // existing purchase never touches its payments (see
                // PurchaseService::update()).
                if (!CONFIG.existing) {
                    payload.payments = this.form.payments
                        .filter(p => Math.abs(parseFloat(p.amount) || 0) > 0.001)
                        .map(p => ({
                            payment_date:     p.payment_date,
                            amount:           Number(p.amount),
                            payment_method:   p.payment_method,
                            reference_number: p.reference_number || null,
                        }));
                }

                return payload;
            },
            submit(post) {
                this.wasValidated = true;
                this.errors = {};

                if (!this.form.supplier_id || !this.form.purchase_date || !this.form.location_id) {
                    this.errors.supplier_id   = !this.form.supplier_id   ? 'Required' : '';
                    this.errors.purchase_date = !this.form.purchase_date ? 'Required' : '';
                    this.errors.location_id   = !this.form.location_id   ? 'Required' : '';
                    return;
                }
                if (this.form.lines.length === 0) {
                    this.setMessage('danger', 'Add at least one item before saving.');
                    return;
                }
                if (this.form.lines.some(l => !l.category_id)) {
                    this.setMessage('danger', 'Every line needs a Stone selected.');
                    return;
                }

                this.submitting = true;
                fetch(CONFIG.submitUrl, {
                    method: CONFIG.submitMethod,
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept':       'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(this.buildPayload(post)),
                })
                .then(async r => {
                    const j = await r.json();
                    if (!r.ok) {
                        if (j.errors) this.errors = this.flattenErrors(j.errors);
                        this.setMessage('danger', j.message || 'Save failed.');
                        return;
                    }
                    window.location.href = j.redirect;
                })
                .catch(err => this.setMessage('danger', 'Save failed: ' + err.message))
                .finally(() => { this.submitting = false; });
            },
            flattenErrors(errs) {
                // Laravel returns nested keys like "lines.0.rows.2.price".
                // Use the first message of each key, keyed by the leaf field
                // for the simple cases we care about (top-level fields).
                const flat = {};
                Object.keys(errs).forEach(k => {
                    flat[k] = Array.isArray(errs[k]) ? errs[k][0] : String(errs[k]);
                });
                return flat;
            },
        },
    });
})();
</script>
