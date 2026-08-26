@extends('layout.app')

@section('title', 'Add Page')

@section('content')

<div class="container-fluid pages-page pages-form-page">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Add Page</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('pages.index') }}">Pages</a></li>
                <li class="breadcrumb-item active">Add</li>
            </ol>
        </div>
    </div>

    <div class="row" id="pageFormApp">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="header-title mb-0">Page Content</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control"
                                :class="{ 'is-invalid': errors.title }"
                                id="title" v-model="form.title" maxlength="191" required
                                @input="onTitleInput"
                                placeholder="e.g. Shipping Policy">
                            <div class="invalid-feedback">@{{ errors.title }}</div>
                        </div>

                        <div class="col-md-12">
                            <label for="slug" class="form-label">Slug</label>
                            <input type="text" class="form-control"
                                :class="{ 'is-invalid': errors.slug }"
                                id="slug" v-model="form.slug" maxlength="191"
                                @input="slugTouched = true"
                                placeholder="auto-generated-from-title">
                            <div class="invalid-feedback">@{{ errors.slug }}</div>
                            <small class="text-muted">URL: /pages/@{{ form.slug || '…' }}</small>
                        </div>

                        <div class="col-md-12">
                            <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" rows="16" id="content"
                                :class="{ 'is-invalid': errors.content }"
                                v-model="form.content"
                                placeholder="Basic HTML tags (e.g. <p>, <b>, <a>) are supported."></textarea>
                            <div class="invalid-feedback">@{{ errors.content }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-light">
                    <h5 class="header-title mb-0">SEO (optional)</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="meta_title" class="form-label">Meta Title</label>
                            <input type="text" class="form-control" id="meta_title" v-model="form.meta_title" maxlength="200">
                        </div>
                        <div class="col-md-12">
                            <label for="meta_description" class="form-label">Meta Description</label>
                            <textarea class="form-control" rows="2" id="meta_description" maxlength="500"
                                v-model="form.meta_description"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div v-if="serverError" class="alert alert-danger">@{{ serverError }}</div>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success" :disabled="submitting" @click="submitForm">
                            <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                            <span v-if="!submitting"><i class="ti ti-device-floppy me-1"></i></span>
                            Create Page
                        </button>
                        <a href="{{ route('pages.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('styles')
<style>
    /* Compact spacing for the Add/Edit Page form — scoped to this page only */
    .pages-form-page { padding-top: 20px; padding-bottom: 20px; }
    .pages-form-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid #e2e8f0;
    }
    .pages-form-page .page-title-head > * { display: flex; align-items: center; }
    .pages-form-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        position: relative;
        padding-left: 12px;
    }
    .pages-form-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0; top: 2px; bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, #1e3a8a, #1d4ed8);
    }
    .pages-form-page .breadcrumb { font-size: 0.75rem; }
    .pages-form-page .card { border-radius: 10px; box-shadow: none; border: 1px solid #e2e8f0; }
    .pages-form-page .card-body { padding: 16px; }
    .pages-form-page .header-title { font-size: 1rem; font-weight: 700; }
    .pages-form-page .mb-3, .pages-form-page .mb-4 { margin-bottom: 12px !important; }
    .pages-form-page .form-label { margin-bottom: 4px; font-size: 0.8125rem; font-weight: 600; }
    .pages-form-page .form-control, .pages-form-page .form-select { padding: 0.4rem 0.65rem; font-size: 0.8125rem; }
    .pages-form-page small.text-muted { display: inline-block; margin-top: 3px; font-size: 0.75rem; }
    .pages-form-page .d-flex.justify-content-end.gap-2 { margin-top: 16px !important; }
</style>
@endpush

@push('scripts')
<script>
    $(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        function slugify(str) {
            return (str || '')
                .toString().toLowerCase().trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        new Vue({
            el: '#pageFormApp',
            data: {
                form: {
                    title: '',
                    slug: '',
                    content: '',
                    meta_title: '',
                    meta_description: '',
                },
                slugTouched: false,
                errors: {},
                submitting: false,
                serverError: null,
            },
            methods: {
                onTitleInput() {
                    if (!this.slugTouched) {
                        this.form.slug = slugify(this.form.title);
                    }
                },
                validateLocal() {
                    this.errors = {};
                    if (!this.form.title.trim()) this.$set(this.errors, 'title', 'Title is required.');
                    if (!this.form.content.trim()) this.$set(this.errors, 'content', 'Content is required.');
                    if (this.form.slug && !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(this.form.slug)) {
                        this.$set(this.errors, 'slug', 'Lowercase letters, numbers, and hyphens only.');
                    }
                    return Object.keys(this.errors).length === 0;
                },
                async submitForm() {
                    this.serverError = null;
                    if (!this.validateLocal()) return;
                    this.submitting = true;

                    try {
                        const res = await fetch('{{ route('pages.store') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(this.form),
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

                        const data = await res.json();
                        window.location.href = data.redirect || '{{ route('pages.index') }}';
                    } catch (err) {
                        this.serverError = 'Network error. Please try again.';
                        this.submitting = false;
                    }
                },
            },
        });
    });
</script>
@endpush
