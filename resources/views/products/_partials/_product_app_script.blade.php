{{-- Shared productApp Vue 2 script — used by both create.blade.php and edit.blade.php.
     The edit page also defines `window.__productBootstrap` BEFORE this script
     runs so the Vue instance can hydrate from existing data on mount. --}}
<script>
    (function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        function makeBlankBarcode(isPrimary) {
            return {
                _uid: 'b_' + Math.random().toString(36).slice(2, 9),
                id: null,
                value: '',
                format: '{{ \App\Models\Barcode::FORMAT_EAN_13 }}',
                label: '',
                is_primary: !!isPrimary,
                channel_ids: [],
                error: null,
                validated: false,
                _validateTimer: null,
            };
        }

        new Vue({
            el: '#productApp',
            data: {
                mode: 'create',
                productId: null,
                form: {
                    title: '',
                    sku: '',
                    top_category_id: null,
                    category_id: null,
                    short_description: '',
                    full_description: '',
                    country_of_origin: '',
                    notes_tags: '',
                    status: false,
                    carat_weight: null,
                    stone_type: null,
                    colour_grade: '',
                    clarity_grade: null,
                    cut_shape: null,
                    treatment: null,
                    certificate_number: '',
                    website_enabled: false,
                    website_price: null,
                    website_title: '',
                    website_description: '',
                    featured_product: false,
                    website_sort_order: null,
                    remove_primary_image: false,
                    remove_certificate_image: false,
                    remove_gallery_ids: [],
                },
                subcategories: [],
                loadingSubcategories: false,
                isGemstone: false,

                primaryImageFile: null,
                primaryImagePreview: null,
                existingPrimaryImage: null,
                galleryFiles: [],
                galleryPreviews: [],
                existingGallery: [],
                certificateFile: null,
                certificatePreview: null,
                existingCertificate: null,

                barcodeMode: 'single',
                barcodes: [makeBlankBarcode(true)],
                barcodesError: null,

                errors: {},
                submitting: false,
                serverError: null,
            },

            mounted() {
                if (window.__productBootstrap) {
                    this.bootstrapFromExisting(window.__productBootstrap);
                }
            },

            methods: {
                /* -------------------- Cascading dropdown -------------------- */
                onTopCategoryChange() {
                    this.form.category_id = null;
                    this.subcategories = [];
                    if (!this.form.top_category_id) {
                        this.isGemstone = false;
                        return;
                    }
                    this.loadSubcategories(this.form.top_category_id);
                    this.recomputeGemstone();
                },

                loadSubcategories(topId) {
                    this.loadingSubcategories = true;
                    fetch('/products/subcategories/' + topId, {
                        headers: { 'Accept': 'application/json' },
                    })
                    .then((r) => r.json())
                    .then((res) => {
                        this.subcategories = res.success ? res.data : [];
                        this.loadingSubcategories = false;
                    })
                    .catch(() => {
                        this.subcategories = [];
                        this.loadingSubcategories = false;
                    });
                },

                recomputeGemstone() {
                    const sel = document.getElementById('top_category_id');
                    if (!sel) { this.isGemstone = false; return; }
                    const opt = sel.options[sel.selectedIndex];
                    // Read the is_gemstone flag stamped onto the <option> by
                    // the Blade partial. '1' = gemstone category.
                    this.isGemstone = !!opt && opt.dataset.gemstone === '1';
                },

                /* -------------------- File handlers -------------------- */
                onPrimaryImageChange(e) {
                    const file = e.target.files[0];
                    if (!file) { this.primaryImageFile = null; this.primaryImagePreview = null; return; }
                    if (!['image/jpeg', 'image/png'].includes(file.type)) {
                        this.$set(this.errors, 'primary_image', 'Image must be JPG or PNG.');
                        return;
                    }
                    if (file.size > 5 * 1024 * 1024) {
                        this.$set(this.errors, 'primary_image', 'Image must not exceed 5 MB.');
                        return;
                    }
                    this.$delete(this.errors, 'primary_image');
                    this.primaryImageFile = file;
                    const reader = new FileReader();
                    reader.onload = (ev) => { this.primaryImagePreview = ev.target.result; };
                    reader.readAsDataURL(file);
                    this.form.remove_primary_image = false;
                },

                onGalleryImageChange(e) {
                    const files = Array.from(e.target.files);
                    const totalAfter = this.existingGallery.length + files.length;
                    if (totalAfter > 10) {
                        alert('You may upload at most 10 gallery images. Currently ' +
                            this.existingGallery.length + ' uploaded + ' + files.length +
                            ' new = too many.');
                        e.target.value = '';
                        return;
                    }
                    this.galleryFiles = files;
                    this.galleryPreviews = [];
                    files.forEach((file) => {
                        const reader = new FileReader();
                        reader.onload = (ev) => this.galleryPreviews.push(ev.target.result);
                        reader.readAsDataURL(file);
                    });
                },

                removeGalleryImage(mediaId) {
                    this.existingGallery = this.existingGallery.filter((g) => g.id !== mediaId);
                    if (!this.form.remove_gallery_ids.includes(mediaId)) {
                        this.form.remove_gallery_ids.push(mediaId);
                    }
                },

                onCertificateChange(e) {
                    const file = e.target.files[0];
                    if (!file) { this.certificateFile = null; this.certificatePreview = null; return; }
                    if (!['image/jpeg', 'image/png', 'application/pdf'].includes(file.type)) {
                        alert('Certificate must be JPG, PNG, or PDF.');
                        return;
                    }
                    if (file.size > 10 * 1024 * 1024) {
                        alert('Certificate must not exceed 10 MB.');
                        return;
                    }
                    this.certificateFile = file;
                    this.certificatePreview = file.name;
                    this.form.remove_certificate_image = false;
                },

                /* -------------------- Barcode methods -------------------- */
                setBarcodeMode(mode) {
                    if (mode === this.barcodeMode) return;
                    if (mode === 'single') {
                        if (this.barcodes.length > 1) {
                            if (!confirm('Switching to Single mode will remove all but the primary barcode. Continue?')) return;
                            const primary = this.barcodes.find((b) => b.is_primary) || this.barcodes[0];
                            this.barcodes = [primary];
                            this.barcodes[0].is_primary = true;
                        }
                    }
                    this.barcodeMode = mode;
                },

                addBarcode() {
                    if (this.barcodes.length >= {{ \App\Models\Barcode::MAX_BARCODES_PER_PRODUCT }}) return;
                    this.barcodes.push(makeBlankBarcode(false));
                },

                removeBarcode(idx) {
                    if (this.barcodes.length <= 1) return;
                    const wasPrimary = this.barcodes[idx].is_primary;
                    this.barcodes.splice(idx, 1);
                    if (wasPrimary && this.barcodes.length > 0) {
                        this.barcodes[0].is_primary = true;
                    }
                },

                setPrimary(idx) {
                    this.barcodes.forEach((b, i) => { b.is_primary = (i === idx); });
                },

                onFormatChange(idx) {
                    const b = this.barcodes[idx];
                    b.error = null;
                    b.validated = false;
                    if (b.value) this.scheduleValidation(idx);
                },

                onValueInput(idx) {
                    const b = this.barcodes[idx];
                    b.error = null;
                    b.validated = false;
                    this.scheduleValidation(idx);
                },

                scheduleValidation(idx) {
                    const b = this.barcodes[idx];
                    if (b._validateTimer) clearTimeout(b._validateTimer);
                    b._validateTimer = setTimeout(() => this.validateBarcode(idx), 400);
                },

                validateBarcode(idx) {
                    const b = this.barcodes[idx];
                    if (!b.value) return;
                    const body = new FormData();
                    body.append('value', b.value);
                    body.append('format', b.format);
                    if (b.id) body.append('ignore_id', b.id);
                    if (this.productId) body.append('product_id', this.productId);

                    fetch('{{ route('products.barcodes.validate') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: body,
                    })
                    .then((r) => r.json())
                    .then((res) => {
                        if (res.success) {
                            this.$set(b, 'error', null);
                            this.$set(b, 'validated', true);
                        } else {
                            this.$set(b, 'error', res.message);
                            this.$set(b, 'validated', false);
                        }
                    })
                    .catch(() => {});
                },

                autoGenerate(idx) {
                    const b = this.barcodes[idx];
                    fetch('{{ route('products.barcodes.generate') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                    .then((r) => r.json())
                    .then((res) => {
                        if (res.success) {
                            b.value = res.value;
                            b.format = res.format;
                            b.error = null;
                            b.validated = true;
                        } else {
                            alert(res.message || 'Could not generate barcode.');
                        }
                    })
                    .catch(() => alert('Network error generating barcode.'));
                },

                /* -------------------- Bootstrap existing product (edit) -------------------- */
                bootstrapFromExisting(data) {
                    this.mode = 'edit';
                    this.productId = data.id;

                    Object.keys(this.form).forEach((k) => {
                        if (data.form && Object.prototype.hasOwnProperty.call(data.form, k)) {
                            this.form[k] = data.form[k];
                        }
                    });
                    this.form.remove_gallery_ids = [];
                    this.form.remove_primary_image = false;
                    this.form.remove_certificate_image = false;

                    this.form.top_category_id = data.top_category_id || null;
                    this.subcategories = data.subcategories || [];
                    this.$nextTick(() => this.recomputeGemstone());

                    this.existingPrimaryImage = data.primary_image_url || null;
                    this.existingGallery = data.gallery || [];
                    this.existingCertificate = data.certificate_url || null;

                    if (Array.isArray(data.barcodes) && data.barcodes.length) {
                        this.barcodes = data.barcodes.map((b) => ({
                            _uid: 'b_' + b.id,
                            id: b.id,
                            value: b.barcode_value,
                            format: b.barcode_format,
                            label: b.barcode_label || '',
                            is_primary: !!b.is_primary,
                            channel_ids: (b.channels || []).map((c) => c.id),
                            error: null,
                            validated: true,
                            _validateTimer: null,
                        }));
                        this.barcodeMode = this.barcodes.length > 1 ? 'multi' : 'single';
                    }
                },

                /* -------------------- Submit -------------------- */
                validateLocal() {
                    this.errors = {};
                    this.barcodesError = null;

                    if (!this.form.title || !String(this.form.title).trim()) {
                        this.$set(this.errors, 'title', 'Title is required.');
                    }
                    if (!this.form.sku || !String(this.form.sku).trim()) {
                        this.$set(this.errors, 'sku', 'SKU is required.');
                    } else if (!/^[A-Za-z0-9_\-]+$/.test(this.form.sku)) {
                        this.$set(this.errors, 'sku', 'Only letters, numbers, hyphens, underscores.');
                    }
                    if (!this.form.category_id) {
                        this.$set(this.errors, 'category_id', 'Please pick a subcategory.');
                    }

                    if (this.isGemstone) {
                        if (!this.form.carat_weight) {
                            this.$set(this.errors, 'carat_weight', 'Carat weight is required for gemstones.');
                        }
                        if (!this.form.stone_type) {
                            this.$set(this.errors, 'stone_type', 'Stone type is required for gemstones.');
                        }
                        if (!this.form.treatment) {
                            this.$set(this.errors, 'treatment', 'Treatment is required for gemstones.');
                        }
                    }

                    if (!this.barcodes.length) {
                        this.barcodesError = 'Please add at least one barcode.';
                    } else {
                        const primaryCount = this.barcodes.filter((b) => b.is_primary).length;
                        if (primaryCount !== 1) {
                            this.barcodesError = primaryCount === 0
                                ? 'Please designate one barcode as Primary.'
                                : 'Only one barcode can be marked as Primary.';
                        }
                        const values = {};
                        this.barcodes.forEach((b, i) => {
                            if (!b.value) {
                                this.$set(b, 'error', 'Required.');
                                return;
                            }
                            if (values[b.value]) {
                                this.$set(b, 'error', 'Duplicate value within this product.');
                            }
                            values[b.value] = true;
                        });
                    }

                    return Object.keys(this.errors).length === 0 && !this.barcodesError;
                },

                async submitForm() {
                    this.serverError = null;
                    if (!this.validateLocal()) {
                        this.$nextTick(() => {
                            const firstInvalid = document.querySelector('.is-invalid');
                            if (firstInvalid) firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        });
                        return;
                    }
                    this.submitting = true;

                    const fd = new FormData();
                    if (this.mode === 'edit') fd.append('_method', 'PUT');

                    const scalarFields = [
                        'title','sku','category_id','short_description','full_description',
                        'country_of_origin','notes_tags','status',
                        'carat_weight','stone_type','colour_grade','clarity_grade',
                        'cut_shape','treatment','certificate_number',
                        'website_enabled','website_price','website_title','website_description',
                        'featured_product','website_sort_order',
                    ];
                    scalarFields.forEach((f) => {
                        const v = this.form[f];
                        if (v === null || v === undefined) return;
                        if (typeof v === 'boolean') fd.append(f, v ? 1 : 0);
                        else fd.append(f, v);
                    });

                    if (this.mode === 'edit') {
                        if (this.form.remove_primary_image)     fd.append('remove_primary_image', 1);
                        if (this.form.remove_certificate_image) fd.append('remove_certificate_image', 1);
                        this.form.remove_gallery_ids.forEach((id) => {
                            fd.append('remove_gallery_ids[]', id);
                        });
                    }

                    if (this.primaryImageFile) fd.append('primary_image', this.primaryImageFile);
                    if (this.certificateFile)  fd.append('certificate_image', this.certificateFile);
                    this.galleryFiles.forEach((file) => fd.append('gallery_images[]', file));

                    const barcodesPayload = this.barcodes.map((b) => ({
                        id: b.id,
                        value: b.value,
                        format: b.format,
                        label: b.label || null,
                        is_primary: !!b.is_primary,
                        channel_ids: (b.channel_ids || []).map((n) => parseInt(n, 10)),
                    }));
                    fd.append('barcodes', JSON.stringify(barcodesPayload));

                    const url = this.mode === 'edit'
                        ? '/products/' + this.productId
                        : '{{ route('products.store') }}';

                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: fd,
                        });

                        if (res.status === 422) {
                            const data = await res.json();
                            const fe = data.errors || {};
                            Object.keys(fe).forEach((k) => {
                                const msg = Array.isArray(fe[k]) ? fe[k][0] : fe[k];
                                const m = k.match(/^barcodes\.(\d+)\.(value|format|is_primary)$/);
                                if (m) {
                                    const i = parseInt(m[1], 10);
                                    if (this.barcodes[i]) this.$set(this.barcodes[i], 'error', msg);
                                    return;
                                }
                                if (k === 'barcodes') { this.barcodesError = msg; return; }
                                this.$set(this.errors, k, msg);
                            });
                            this.submitting = false;
                            this.$nextTick(() => {
                                const firstInvalid = document.querySelector('.is-invalid');
                                if (firstInvalid) firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            });
                            return;
                        }
                        if (!res.ok) {
                            const data = await res.json().catch(() => ({}));
                            this.serverError = data.message || 'Something went wrong.';
                            this.submitting = false;
                            return;
                        }

                        const data = await res.json();
                        window.location.href = data.redirect || '{{ route('products.index') }}';
                    } catch (err) {
                        this.serverError = 'Network error. Please try again.';
                        this.submitting = false;
                    }
                },
            },
        });
    })();
</script>
