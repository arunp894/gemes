@extends('layout.app')

@section('title', 'Categories')

@section('content')

<div class="container-fluid">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Categories</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Paces</a></li>
                <li class="breadcrumb-item"><a href="#">Catalogue</a></li>
                <li class="breadcrumb-item active">Categories</li>
            </ol>
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                {{-- Card header: search + per-page + status filter + add button --}}
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="app-search">
                            <input id="categorySearch" type="search" class="form-control"
                                placeholder="Search category..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div>
                            <select id="categoryPerPage" class="form-select form-control my-1 my-md-0">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                        </div>

                        <div class="app-search">
                            <select id="categoryStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        <a href="#!" class="btn btn-primary ms-1" data-bs-toggle="modal"
                            data-bs-target="#addCategoryModal">
                            <i class="ti ti-plus fs-sm me-2"></i> Add Category
                        </a>
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="categoriesTable" class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">
                                    <input id="categorySelectAll" class="form-check-input form-check-input-light fs-14 mt-0"
                                        type="checkbox" />
                                </th>
                                <th>Category Name</th>
                                <th>Code</th>
                                <th>Subcategories</th>
                                <th>Display Order</th>
                                <th>Status</th>
                                <th>Last Modified</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- Card footer: DataTables info + pagination get moved here --}}
                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="categoriesInfoSlot" class="text-muted small"></div>
                        <div id="categoriesPaginationSlot"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Add Category Modal ==================== --}}
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" id="addCategoryModalApp">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">
                        <i class="ti ti-plus me-1"></i>
                        @{{ form.parent_id ? 'Add New Subcategory' : 'Add New Category' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="quickAddForm" novalidate @submit.prevent="submitForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            {{-- Parent --}}
                            <div class="col-md-6">
                                <label for="quick_parent_id" class="form-label">Parent Category</label>
                                <select class="form-select" id="quick_parent_id" v-model="form.parent_id">
                                    <option :value="null">— None (Top-Level) —</option>
                                    @foreach ($parents as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Leave blank for top-level. Pick a parent to create a subcategory.</small>
                            </div>

                            {{-- Status --}}
                            <div class="col-md-6">
                                <label class="form-label d-block">Status <span class="text-danger">*</span></label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="quick_status"
                                        v-model="form.status">
                                    <label class="form-check-label" for="quick_status">
                                        @{{ form.status ? 'Active' : 'Inactive' }}
                                    </label>
                                </div>
                            </div>

                            {{-- Name --}}
                            <div class="col-md-6">
                                <label for="quick_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.name }"
                                    id="quick_name" v-model="form.name" maxlength="150" required>
                                <div class="invalid-feedback">@{{ errors.name }}</div>
                            </div>

                            {{-- Code --}}
                            <div class="col-md-6">
                                <label for="quick_code" class="form-label">Category Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase"
                                    :class="{ 'is-invalid': errors.code }"
                                    id="quick_code" v-model="form.code" maxlength="50" required>
                                <div class="invalid-feedback">@{{ errors.code }}</div>
                                <small class="text-muted">Letters, numbers, underscores only.</small>
                            </div>

                            {{-- Image --}}
                            <div class="col-md-12">
                                <label for="quick_image" class="form-label">Category Image</label>
                                <input type="file" class="form-control" :class="{ 'is-invalid': errors.image }"
                                    id="quick_image" accept="image/jpeg,image/png" @change="onImageChange">
                                <div class="invalid-feedback">@{{ errors.image }}</div>
                                <small class="text-muted">JPG or PNG, max 2 MB.</small>
                            </div>

                            {{-- Description --}}
                            <div class="col-md-12">
                                <label for="quick_description" class="form-label">Description (optional)</label>
                                <textarea class="form-control" id="quick_description" v-model="form.description"
                                    rows="2" maxlength="1000" placeholder="Brief description..."></textarea>
                            </div>
                        </div>

                        <div v-if="serverError" class="alert alert-danger mt-3 mb-0">@{{ serverError }}</div>
                    </div>

                    <div class="modal-footer">
                        <a href="{{ route('categories.create') }}" class="btn btn-light me-auto">
                            <i class="ti ti-external-link me-1"></i>Open Full Form
                        </a>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" :disabled="submitting">
                            <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                            Add Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- ==================== /Modal ==================== --}}

</div>

@endsection

@push('styles')
<style>
    /* Tighten the app-search wrapper so the dropdowns look right in the card header */
    .app-search { position: relative; }
    .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .app-search > .form-control { padding-right: 2.25rem; min-width: 180px; }

    /* Hide DataTables built-in length+filter+info+paginate (we render our own slots) */
    #categoriesTable_wrapper .dataTables_length,
    #categoriesTable_wrapper .dataTables_filter { display: none !important; }

    /* The cloned DataTables info/pagination land inside our card-footer slots */
    #categoriesInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #categoriesPaginationSlot .pagination { margin-bottom: 0; }
    #categoriesPaginationSlot .dataTables_paginate { margin: 0; }
</style>
@endpush

@push('scripts')
<script>
    $(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        // ============= DataTable =============
        const dt = $('#categoriesTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[4, 'asc'], [1, 'asc']], // display_order asc, then name asc
            ajax: {
                url: '{{ route('categories.data') }}',
                type: 'GET',
            },
            // dom: render table + (info+paginate) into a hidden wrapper; we'll move them via initComplete
            dom: 'rt<"d-none datatables-tail"ip>',
            pageLength: 10,
            columns: [
                { data: 'checkbox',            name: 'checkbox',                  orderable: false, searchable: false, className: 'ps-3' },
                { data: 'name',                name: 'categories.name' },
                { data: 'code',                name: 'categories.code' },
                { data: 'subcategories_count', name: 'subcategories_count',       orderable: false, searchable: false },
                { data: 'display_order',       name: 'categories.display_order' },
                { data: 'status_badge',        name: 'categories.status',         searchable: true },
                { data: 'updated_at',          name: 'categories.updated_at' },
                { data: 'action',              name: 'action',                    orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ categories',
                infoEmpty: 'No categories found',
                infoFiltered: ' (filtered from _MAX_ total)',
                emptyTable: 'No categories yet. Click "Add Category" to get started.',
                zeroRecords: 'No categories match your search.',
                processing: '<div class="spinner-border spinner-border-sm text-primary"></div>',
                paginate: {
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next:     '<i class="ti ti-chevron-right"></i>',
                },
            },
            initComplete: function () {
                // Move DataTables-rendered info & pagination into our card-footer slots
                $('#categoriesInfoSlot').append($('#categoriesTable_info'));
                $('#categoriesPaginationSlot').append($('#categoriesTable_paginate'));
            },
        });

        // ============= Custom search wire-up (debounced) =============
        let searchTimer;
        $('#categorySearch').on('keyup', function () {
            clearTimeout(searchTimer);
            const v = this.value;
            searchTimer = setTimeout(() => dt.search(v).draw(), 250);
        });

        // Per-page
        $('#categoryPerPage').on('change', function () {
            dt.page.len(parseInt(this.value, 10)).draw();
        });

        // Status filter — column index 5 is the status_badge column
        $('#categoryStatusFilter').on('change', function () {
            dt.column(5).search(this.value).draw();
        });

        // ============= Select-all =============
        $('#categorySelectAll').on('change', function () {
            $('#categoriesTable tbody .product-item-check').prop('checked', this.checked);
        });

        // ============= Toggle Status =============
        $('#categoriesTable tbody').on('click', '.js-toggle-status', function () {
            const url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) { if (res.success) dt.ajax.reload(null, false); },
                error: function () { alert('Failed to update status.'); },
            });
        });

        // ============= Delete =============
        $('#categoriesTable tbody').on('click', '.js-delete', function () {
            const url  = $(this).data('url');
            const name = $(this).data('name');
            if (!confirm('Delete category "' + name + '"? (This is a soft delete.)')) return;
            $.ajax({
                url: url,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) dt.ajax.reload(null, false);
                    else alert(res.message || 'Could not delete category.');
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to delete category.';
                    alert(msg);
                },
            });
        });

        // ============= Quick-add modal (Vue) =============
        const quickAddVm = new Vue({
            el: '#addCategoryModalApp',
            data: {
                form: {
                    parent_id: null,
                    name: '',
                    code: '',
                    description: '',
                    status: true,
                },
                imageFile: null,
                errors: {},
                submitting: false,
                serverError: null,
            },
            methods: {
                onImageChange(e) {
                    this.$delete(this.errors, 'image');
                    const file = e.target.files[0];
                    if (!file) { this.imageFile = null; return; }
                    if (!['image/jpeg', 'image/png'].includes(file.type)) {
                        this.$set(this.errors, 'image', 'Image must be JPG or PNG.');
                        return;
                    }
                    if (file.size > 2 * 1024 * 1024) {
                        this.$set(this.errors, 'image', 'Image must not exceed 2 MB.');
                        return;
                    }
                    this.imageFile = file;
                },
                resetForm() {
                    this.form = { parent_id: null, name: '', code: '', description: '', status: true };
                    this.imageFile = null;
                    this.errors = {};
                    this.serverError = null;
                    this.submitting = false;
                    const imgInput = document.getElementById('quick_image');
                    if (imgInput) imgInput.value = '';
                },
                validateLocal() {
                    this.errors = {};
                    if (!this.form.name || !this.form.name.trim()) {
                        this.$set(this.errors, 'name', 'Name is required.');
                    }
                    if (!this.form.code || !this.form.code.trim()) {
                        this.$set(this.errors, 'code', 'Code is required.');
                    } else if (!/^[A-Za-z0-9_]+$/.test(this.form.code)) {
                        this.$set(this.errors, 'code', 'Only letters, numbers, underscores.');
                    }
                    return Object.keys(this.errors).length === 0;
                },
                async submitForm() {
                    this.serverError = null;
                    if (!this.validateLocal()) return;
                    this.submitting = true;

                    const fd = new FormData();
                    fd.append('name', this.form.name);
                    fd.append('code', this.form.code);
                    fd.append('description', this.form.description || '');
                    if (this.form.parent_id) fd.append('parent_id', this.form.parent_id);
                    fd.append('display_order', 0);
                    fd.append('status', this.form.status ? 1 : 0);
                    if (this.imageFile) fd.append('image', this.imageFile);

                    try {
                        const res = await fetch('{{ route('categories.store') }}', {
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
                            Object.keys(fe).forEach((k) => this.$set(this.errors, k, fe[k][0]));
                            this.submitting = false;
                            return;
                        }
                        if (!res.ok) {
                            const data = await res.json().catch(() => ({}));
                            this.serverError = data.message || 'Something went wrong.';
                            this.submitting = false;
                            return;
                        }

                        // Success: close modal, reload table, reset form
                        const modalEl = document.getElementById('addCategoryModal');
                        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                        modal.hide();
                        this.resetForm();
                        dt.ajax.reload(null, false);
                    } catch (err) {
                        this.serverError = 'Network error. Please try again.';
                        this.submitting = false;
                    }
                },
            },
        });

        // Reset the form whenever the modal closes (so reopening is clean)
        document.getElementById('addCategoryModal').addEventListener('hidden.bs.modal', () => {
            quickAddVm.resetForm();
        });
    });
</script>
@endpush
