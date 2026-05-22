{{-- Vue 2 application for the purchase create/edit form.

     Mounting:
       new Vue({ el: '#purchaseFormApp', data, methods })

     Configuration (interpolated via Blade above the script):
       mode             create|edit
       suppliersJson    JSON of supplier list   (id, name, company_name, supplier_code, invoice_prefix, gst_number)
       racksJson        JSON of rack list       (id, code, name)
       lookupUrl        GET endpoint: barcode -> product
       searchUrl        GET endpoint: q -> products
       previewUrl       GET endpoint: supplier_id, date -> next invoice number
       submitUrl        POST/PUT endpoint for save
       submitMethod     POST or PUT
       existingPurchase null on create, hydrated Purchase model on edit
--}}
<script>
(function () {
    // ── Configuration shipped from Blade ───────────────────────────
    const CONFIG = {
        mode:        @json($mode),
        suppliers:   {!! $suppliersJson !!},
        racks:       {!! $racksJson !!},
        lookupUrl:   @json($lookupUrl),
        searchUrl:   @json($searchUrl),
        previewUrl:  @json($previewUrl),
        submitUrl:   @json($submitUrl),
        submitMethod:@json($submitMethod),
        existing:    {!! $existingPurchase ? $existingPurchase->toJson() : 'null' !!},
    };

    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    // ── Helpers ─────────────────────────────────────────────────────
    function emptyRow(qty) {
        return {
            qty:              qty || 0,
            barcode:          '',
            rack_id:          null,
            serial_number:    null,
            price:            0,
            tax_percent:      0,
            discount_percent: 0,
            expiry_date:      null,
            manufacture_date: null,
            remarks:          null,
            _focused:         false,
        };
    }

    function newLineFromProduct(product, scannedBarcode) {
        const pkg     = product.packaging || {};
        const type    = 'piece';                                      // always default to piece
const innerN  = parseInt(pkg.inner_pack_contains, 10) || 1;

const packageQty = 1;

// piece -> 1 row; box -> packageQty rows
const innerRows = 1;                                          // piece default
const perRowQty = 1;


        const rows = [];
        for (let i = 0; i < innerRows; i++) {
            const r = emptyRow(perRowQty);
            if (i === 0 && scannedBarcode) {
                r.barcode = scannedBarcode;
            }
            rows.push(r);
        }

        return {
            _product:       product,
            _highlight:     true,
            _expanded:      true,
            product_id:     product.id,
            type:           type,
            package_name:   pkg.inner_pack_name || (type === 'piece' ? 'Piece' : 'Box'),
            package_qty:    packageQty,
            unit_contains:  (type === 'piece') ? null : innerN,
            remarks:        null,
            rows:           rows,
        };
    }

    // ── Hydrate from an existing purchase (edit mode) ───────────────
    function hydrateLines(purchase) {
        if (!purchase || !Array.isArray(purchase.lines)) return [];

        return purchase.lines.map(l => ({
            _product: {
                id: l.product_id,
                title: (l.product && l.product.title) || 'Product #' + l.product_id,
                sku:   (l.product && l.product.sku)   || '',
                packaging: l.product ? {
                    pack_type:           l.product.pack_type,
                    outer_pack_name:     l.product.outer_pack_name,
                    outer_pack_contains: l.product.outer_pack_contains,
                    inner_pack_name:     l.product.inner_pack_name,
                    inner_pack_contains: l.product.inner_pack_contains,
                } : {},
            },
            _highlight:    false,
            _expanded: false,
            product_id:    l.product_id,
            type:          l.type,
            package_name:  l.package_name,
            package_qty:   l.package_qty,
            unit_contains: l.unit_contains,
            remarks:       l.remarks,
            rows: (l.rows || []).map(r => ({
                qty:              r.qty,
                barcode:          r.barcode || '',
                rack_id:          r.rack_id,
                serial_number:    r.serial_number,
                price:            parseFloat(r.price)            || 0,
                tax_percent:      parseFloat(r.tax_percent)      || 0,
                discount_percent: parseFloat(r.discount_percent) || 0,
                expiry_date:      r.expiry_date,
                manufacture_date: r.manufacture_date,
                remarks:          r.remarks,
                _focused:         false,
            })),
        }));
    }

    // ── Vue instance ────────────────────────────────────────────────
    new Vue({
        el: '#purchaseFormApp',
        data: {
            suppliers:      CONFIG.suppliers,
            racks:          CONFIG.racks,
            barcodeInput:   '',
            productSearch:  '',
            searchResults:  [],
            scannerMessage: '',
            scannerLevel:   '',   // 'success' | 'danger' | 'info'
            submitting:     false,
            wasValidated:   false,
            errors:         {},

            form: {
                supplier_id:            CONFIG.existing ? CONFIG.existing.supplier_id : null,
                purchase_date:          CONFIG.existing ? CONFIG.existing.purchase_date : new Date().toISOString().slice(0, 10),
                invoice_number_preview: CONFIG.existing ? CONFIG.existing.invoice_number : '',
                tax_type:               CONFIG.existing ? CONFIG.existing.tax_type : 'none',
                paid_amount:            CONFIG.existing ? parseFloat(CONFIG.existing.paid_amount) || 0 : 0,
                note:                   CONFIG.existing ? CONFIG.existing.note : '',
                lines:                  CONFIG.existing ? hydrateLines(CONFIG.existing) : [],
            },
        },

        computed: {
            totals() {
                let subtotal = 0, discount = 0, tax = 0;

                this.form.lines.forEach(line => {
                    line.rows.forEach(r => {
                        const qty   = parseFloat(r.qty)   || 0;
                        const price = parseFloat(r.price) || 0;
                        const gross = qty * price;
                        const disc  = gross * ((parseFloat(r.discount_percent) || 0) / 100);
                        const tx    = (gross - disc) * ((parseFloat(r.tax_percent) || 0) / 100);

                        subtotal += gross;
                        discount += disc;
                        tax      += tx;
                    });
                });

                const grand = subtotal - discount + tax;
                const due   = Math.max(0, grand - (parseFloat(this.form.paid_amount) || 0));

                return { subtotal, discount, tax, grand, due };
            },
            totalRows() {
                return this.form.lines.reduce((acc, l) => acc + l.rows.length, 0);
            },
            totalPiecesAll() {
                return this.form.lines.reduce((acc, l) => acc + this.totalPieces(l), 0);
            },
            scannerAlertClass() {
                return {
                    'alert-success': this.scannerLevel === 'success',
                    'alert-danger':  this.scannerLevel === 'danger',
                    'alert-info':    this.scannerLevel === 'info' || !this.scannerLevel,
                };
            },
            scannerIconClass() {
                return {
                    'ti me-1':         true,
                    'ti-circle-check': this.scannerLevel === 'success',
                    'ti-alert-circle': this.scannerLevel === 'danger',
                    'ti-info-circle':  this.scannerLevel === 'info' || !this.scannerLevel,
                };
            },
        },

        mounted() {
            if (this.form.supplier_id && !CONFIG.existing) {
                this.refreshInvoiceNumber();
            }
        },

        methods: {

            /* ─── Money / row math ──────────────────────────────── */

            formatMoney(v) {
                const n = parseFloat(v) || 0;
                return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            rowNet(r) {
                const qty   = parseFloat(r.qty)   || 0;
                const price = parseFloat(r.price) || 0;
                const gross = qty * price;
                const disc  = gross * ((parseFloat(r.discount_percent) || 0) / 100);
                const tx    = (gross - disc) * ((parseFloat(r.tax_percent) || 0) / 100);
                return gross - disc + tx;
            },
            lineNet(line) {
                return line.rows.reduce((acc, r) => acc + this.rowNet(r), 0);
            },
            totalPieces(line) {
                if (line.type === 'piece') {
                    return line.rows.reduce((acc, r) => acc + (parseInt(r.qty, 10) || 0), 0);
                }
                return line.rows.reduce((acc, r) => acc + (parseInt(r.qty, 10) || 0), 0);
            },

            packBadge(pkg) {
                if (pkg.pack_type === 'carton') {
                    return `1 ${pkg.outer_pack_name || 'Ctn'} × ${pkg.outer_pack_contains} ${pkg.inner_pack_name || 'Box'} × ${pkg.inner_pack_contains} pcs`;
                }
                if (pkg.pack_type === 'unit') {
                    return `1 ${pkg.inner_pack_name || 'Unit'} × ${pkg.inner_pack_contains} pcs`;
                }
                return 'Piece';
            },

            /* ─── Supplier / invoice number ──────────────────────── */

            onSupplierChange() {
                this.refreshInvoiceNumber();
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

            /* ─── Barcode scan ───────────────────────────────────── */

            onBarcodeEnter() {
                const code = this.barcodeInput.trim();
                if (!code) return;

                // If this barcode already lives on a row, just bump qty there.
                const existing = this.findRowByBarcode(code);
                if (existing) {
                    existing.row.qty = (parseInt(existing.row.qty, 10) || 0) + 1;
                    this.flashLine(existing.lineIdx);
                    this.setScanner('info', `Incremented qty on existing row for "${code}".`);
                    this.barcodeInput = '';
                    return;
                }

                const url = `${CONFIG.lookupUrl}?barcode=${encodeURIComponent(code)}`;
                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(async r => {
                        const j = await r.json();
                        if (!r.ok || !j.ok) {
                            this.setScanner('danger', j.message || 'Barcode not found.');
                            return;
                        }
                        this.addProduct(j.product, code);
                        this.setScanner('success', `Added ${j.product.title}.`);
                    })
                    .catch(err => this.setScanner('danger', 'Lookup failed: ' + err.message))
                    .finally(() => {
                        this.barcodeInput = '';
                        this.$nextTick(() => this.$refs.barcodeInput?.focus());
                    });
            },
            findRowByBarcode(code) {
                for (let li = 0; li < this.form.lines.length; li++) {
                    const line = this.form.lines[li];
                    for (const row of line.rows) {
                        if (row.barcode === code) {
                            return { lineIdx: li, row };
                        }
                    }
                }
                return null;
            },
            setScanner(level, msg) {
                this.scannerLevel   = level;
                this.scannerMessage = msg;
                if (level === 'success' || level === 'info') {
                    setTimeout(() => { if (this.scannerMessage === msg) this.scannerMessage = ''; }, 3000);
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
            /* ─── Product search ─────────────────────────────────── */

            onSearchInput() {
                const term = this.productSearch.trim();
                if (term.length < 1) { this.searchResults = []; return; }

                fetch(`${CONFIG.searchUrl}?q=${encodeURIComponent(term)}`, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(j => { this.searchResults = j.items || []; })
                    .catch(() => { this.searchResults = []; });
            },
            addProduct(product, scannedBarcode) {
                this.form.lines.push(newLineFromProduct(product, scannedBarcode));
                this.searchResults = [];
                this.productSearch = '';
                this.flashLine(this.form.lines.length - 1);
            },

            /* ─── Row management ─────────────────────────────────── */

            rebuildRows(lineIdx) {
                const line = this.form.lines[lineIdx];
                const pkg  = line._product.packaging || {};
                if (line.type === 'piece') return;

                const outerN     = parseInt(pkg.outer_pack_contains, 10) || 1;
                const innerN     = parseInt(pkg.inner_pack_contains, 10) || 1;
                const packageQty = parseInt(line.package_qty, 10) || 1;

                const expected = (line.type === 'box') ? packageQty : 1;

                // Grow or shrink to `expected` rows, preserving existing data.
                if (line.rows.length < expected) {
                    while (line.rows.length < expected) {
                        // Use the first row as a template so price/tax cascades.
                        const t = line.rows[0] || emptyRow(innerN);
                        line.rows.push({
                            qty:              t.qty || 1,
                            barcode:          '',
                            rack_id:          t.rack_id,
                            serial_number:    null,
                            price:            t.price,
                            tax_percent:      t.tax_percent,
                            discount_percent: t.discount_percent,
                            expiry_date:      t.expiry_date,
                            manufacture_date: null,
                            remarks:          null,
                            _focused:         false,
                        });
                    }
                } else if (line.rows.length > expected) {
                    line.rows.splice(expected);
                }
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
                } else {
                    this.$nextTick(() => this.$refs.barcodeInput?.focus());
                }
            },
            removeLine(li) {
                if (!confirm('Remove this product from the purchase?')) return;
                this.form.lines.splice(li, 1);
            },
            resetForm() {
                if (!confirm('Clear all lines?')) return;
                this.form.lines = [];
            },

            /* ─── Submit ─────────────────────────────────────────── */

            buildPayload(post) {
                return {
                    supplier_id:   this.form.supplier_id,
                    purchase_date: this.form.purchase_date,
                    tax_type:      this.form.tax_type,
                    note:          this.form.note,
                    paid_amount:   this.form.paid_amount,
                    status:        post ? 'posted' : 'draft',
                    lines: this.form.lines.map(l => ({
                        product_id:    l.product_id,
                        type:          l.type,
                        package_name:  l.package_name,
                        package_qty:   l.package_qty,
                        unit_contains: l.unit_contains,
                        remarks:       l.remarks,
                        rows: l.rows.map(r => ({
                            qty:              r.qty,
                            barcode:          r.barcode || null,
                            rack_id:          r.rack_id,
                            serial_number:    r.serial_number,
                            price:            r.price,
                            tax_percent:      r.tax_percent,
                            discount_percent: r.discount_percent,
                            expiry_date:      r.expiry_date,
                            manufacture_date: r.manufacture_date,
                            remarks:          r.remarks,
                        })),
                    })),
                };
            },
            submit(post) {
                this.wasValidated = true;
                this.errors = {};

                if (!this.form.supplier_id || !this.form.purchase_date) {
                    this.errors.supplier_id   = !this.form.supplier_id   ? 'Required' : '';
                    this.errors.purchase_date = !this.form.purchase_date ? 'Required' : '';
                    return;
                }
                if (this.form.lines.length === 0) {
                    this.setScanner('danger', 'Add at least one product before saving.');
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
                        this.setScanner('danger', j.message || 'Save failed.');
                        return;
                    }
                    window.location.href = j.redirect;
                })
                .catch(err => this.setScanner('danger', 'Save failed: ' + err.message))
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
