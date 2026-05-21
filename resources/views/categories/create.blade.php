@extends('layout.app')

@section('title', 'Add New Category')

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Add New Category</h4>
        </div>

        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Paces</a></li>
                <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Categories</a></li>
                <li class="breadcrumb-item active">Add New</li>
            </ol>
        </div>
    </div>

    <div id="categoryCreateApp" class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Category Details</h4>
                    <p class="text-muted mb-4">Fields marked with <span class="text-danger">*</span> are required.</p>

                    <form id="categoryForm" class="needs-validation" novalidate @submit.prevent="submitForm($event)">
                        @csrf

                        {{-- Parent --}}
                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Parent Category</label>
                            <select class="form-select" id="parent_id" name="parent_id" v-model="form.parent_id">
                                <option :value="null">— None (Top-Level Category) —</option>
                                @foreach ($parents as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                Leave blank to create a <strong>top-level category</strong>.
                                Pick a parent to create a <strong>subcategory</strong> instead.
                            </small>
                        </div>

                        {{-- Name --}}
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                @{{ form.parent_id ? 'Subcategory Name' : 'Category Name' }}
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control"
                                :class="{ 'is-invalid': errors.name, 'is-valid': touched.name && !errors.name && form.name }"
                                id="name" name="name" v-model="form.name" @input="validateField('name')"
                                @blur="touched.name = true" maxlength="150" required>
                            <div class="invalid-feedback" v-if="errors.name">@{{ errors.name }}</div>
                            <small class="text-muted">Unique name (e.g. Gemstones, Jewellery, Rubies).</small>
                        </div>

                        {{-- Code --}}
                        <div class="mb-3">
                            <label for="code" class="form-label">Category Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase"
                                :class="{ 'is-invalid': errors.code, 'is-valid': touched.code && !errors.code && form.code }"
                                id="code" name="code" v-model="form.code" @input="validateField('code')"
                                @blur="touched.code = true" maxlength="50" pattern="^[A-Za-z0-9_]+$" required>
                            <div class="invalid-feedback" v-if="errors.code">@{{ errors.code }}</div>
                            <small class="text-muted">Letters, numbers, underscores only. No spaces (e.g. GEM, JWL, GEM_RUB).</small>
                        </div>

                        {{-- Description --}}
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" :class="{ 'is-invalid': errors.description }" id="description"
                                name="description" v-model="form.description" rows="3" maxlength="1000"></textarea>
                            <div class="invalid-feedback" v-if="errors.description">@{{ errors.description }}</div>
                            <small class="text-muted">Optional. @{{ (form.description || '').length }}/1000 characters.</small>
                        </div>

                        {{-- Display Order --}}
                        <div class="mb-3">
                            <label for="display_order" class="form-label">Display Order</label>
                            <input type="number" class="form-control" :class="{ 'is-invalid': errors.display_order }"
                                id="display_order" name="display_order" v-model.number="form.display_order" min="0"
                                max="99999">
                            <div class="invalid-feedback" v-if="errors.display_order">@{{ errors.display_order }}</div>
                            <small class="text-muted">Lower numbers appear first. Default: alphabetical.</small>
                        </div>

                        {{-- Image --}}
                        <div class="mb-3">
                            <label for="image" class="form-label">Category Image</label>
                            <input type="file" class="form-control" :class="{ 'is-invalid': errors.image }" id="image"
                                name="image" accept="image/jpeg,image/png" @change="onImageChange">
                            <div class="invalid-feedback" v-if="errors.image">@{{ errors.image }}</div>
                            <small class="text-muted">JPG or PNG, max 2 MB.</small>

                            <div class="mt-2" v-if="imagePreview">
                                <img :src="imagePreview" alt="Preview" class="rounded border"
                                    style="width:120px;height:120px;object-fit:cover;">
                            </div>
                        </div>

                        {{-- Gemstone-Type flag (top-level only) --}}
                        <div class="mb-3" v-show="!form.parent_id">
                            <label class="form-label d-block">Gemstone Category</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_gemstone"
                                    v-model="form.is_gemstone">
                                <label class="form-check-label" for="is_gemstone">
                                    Products under this category use gemstone fields (carat, treatment, certificate…)
                                </label>
                            </div>
                            <small class="text-muted">
                                Tick this if products in this category are gemstones or certified stones. The product
                                form will then show the Gemstone Details panel.
                            </small>
                        </div>

                        {{-- Status --}}
                        <div class="mb-3">
                            <label class="form-label d-block">Status <span class="text-danger">*</span></label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="status"
                                    v-model="form.status">
                                <label class="form-check-label" for="status">
                                    @{{ form.status ? 'Active' : 'Inactive' }}
                                </label>
                            </div>
                            <small class="text-muted">Inactive categories are hidden from product creation forms.</small>
                        </div>

                        {{-- Server error banner --}}
                        <div v-if="serverError" class="alert alert-danger" role="alert">@{{ serverError }}</div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('categories.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary" :disabled="submitting">
                                <span v-if="submitting" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                <span v-if="!submitting"><i class="ti ti-device-floppy me-1"></i></span>
                                Save Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="header-title">Tips</h5>
                    <ul class="text-muted small mb-0 ps-3">
                        <li><strong>Top-level vs subcategory:</strong> the Parent Category dropdown decides. Leave blank for top-level.</li>
                        <li>Names must be unique across the platform.</li>
                        <li>Codes cannot contain spaces; underscores are allowed.</li>
                        <li>Subcategory codes typically follow <code>PARENT_CHILD</code> (e.g. <code>GEM_RUB</code>).</li>
                        <li>Inactive categories stay linked to existing products.</li>
                        <li>Categories with linked subcategories or products cannot be deleted &mdash; deactivate them instead.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    new Vue({
        el: '#categoryCreateApp',
        data: {
            form: {
                parent_id: null,
                name: '',
                code: '',
                description: '',
                display_order: 0,
                status: true,
                is_gemstone: false,
            },
            imageFile: null,
            imagePreview: null,
            errors: {},
            touched: {},
            submitting: false,
            serverError: null,
        },
        methods: {
            validateField(field) {
                this.touched[field] = true;
                const value = this.form[field];

                if (field === 'name') {
                    if (!value || !value.trim()) this.$set(this.errors, 'name', 'Category name is required.');
                    else if (value.length > 150)  this.$set(this.errors, 'name', 'Maximum 150 characters.');
                    else this.$delete(this.errors, 'name');
                }

                if (field === 'code') {
                    if (!value || !value.trim()) this.$set(this.errors, 'code', 'Category code is required.');
                    else if (!/^[A-Za-z0-9_]+$/.test(value)) this.$set(this.errors, 'code', 'Only letters, numbers, and underscores allowed.');
                    else if (value.length > 50)  this.$set(this.errors, 'code', 'Maximum 50 characters.');
                    else this.$delete(this.errors, 'code');
                }
            },
            onImageChange(e) {
                this.$delete(this.errors, 'image');
                const file = e.target.files[0];
                if (!file) { this.imageFile = null; this.imagePreview = null; return; }
                if (!['image/jpeg', 'image/png'].includes(file.type)) {
                    this.$set(this.errors, 'image', 'Image must be JPG or PNG.');
                    return;
                }
                if (file.size > 2 * 1024 * 1024) {
                    this.$set(this.errors, 'image', 'Image must not exceed 2 MB.');
                    return;
                }
                this.imageFile = file;
                const reader = new FileReader();
                reader.onload = (ev) => { this.imagePreview = ev.target.result; };
                reader.readAsDataURL(file);
            },
            validateAll() {
                this.validateField('name');
                this.validateField('code');
                return Object.keys(this.errors).length === 0;
            },
            async submitForm(ev) {
                this.serverError = null;
                if (!this.validateAll()) return;
                this.submitting = true;

                const fd = new FormData();
                if (this.form.parent_id) fd.append('parent_id', this.form.parent_id);
                fd.append('name', this.form.name);
                fd.append('code', this.form.code);
                fd.append('description', this.form.description || '');
                fd.append('display_order', this.form.display_order || 0);
                fd.append('status', this.form.status ? 1 : 0);
                // Only send is_gemstone when creating a top-level category;
                // for subcategories the controller forces it false anyway.
                if (!this.form.parent_id) {
                    fd.append('is_gemstone', this.form.is_gemstone ? 1 : 0);
                }
                if (this.imageFile) fd.append('image', this.imageFile);

                try {
                    const res = await fetch('{{ route('categories.store') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: fd,
                    });

                    if (res.status === 422) {
                        const data = await res.json();
                        const fieldErrors = data.errors || {};
                        Object.keys(fieldErrors).forEach((k) => this.$set(this.errors, k, fieldErrors[k][0]));
                        this.submitting = false;
                        return;
                    }

                    if (!res.ok) {
                        const data = await res.json().catch(() => ({}));
                        this.serverError = data.message || 'Something went wrong. Please try again.';
                        this.submitting = false;
                        return;
                    }

                    const data = await res.json();
                    window.location.href = data.redirect || '{{ route('categories.index') }}';
                } catch (err) {
                    this.serverError = 'Network error. Please check your connection.';
                    this.submitting = false;
                }
            },
        },
    });
</script>
@endpush
