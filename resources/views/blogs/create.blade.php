@extends('layout.app')

@section('title', 'Add Blog Post')

@section('content')

<div class="container-fluid blogs-page blogs-form-page">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Add Blog Post</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('blogs.index') }}">Blog</a></li>
                <li class="breadcrumb-item active">Add</li>
            </ol>
        </div>
    </div>

    <div class="row" id="blogFormApp">
        <div class="col-xl-8">

            {{-- ─────────────────  Post  ───────────────── --}}
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Post</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control"
                                :class="{ 'is-invalid': errors.title }"
                                id="title" v-model="form.title" maxlength="191" required
                                @input="onTitleInput"
                                placeholder="e.g. How to Care for a Tanzanite Ring">
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
                            <small class="text-muted">URL: /blog/@{{ form.slug || '…' }} — leave blank to auto-generate from the title.</small>
                        </div>

                        <div class="col-md-12">
                            <label for="excerpt" class="form-label">Excerpt</label>
                            <textarea class="form-control" rows="2" id="excerpt" maxlength="500"
                                :class="{ 'is-invalid': errors.excerpt }"
                                v-model="form.excerpt"
                                placeholder="Short summary shown on the blog listing and in search results. Leave blank to auto-derive from the content."></textarea>
                            <div class="invalid-feedback">@{{ errors.excerpt }}</div>
                        </div>

                        <div class="col-md-12">
                            <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" rows="14" id="content"
                                :class="{ 'is-invalid': errors.content }"
                                v-model="form.content"
                                placeholder="Write the post content here. Basic HTML tags (e.g. <p>, <b>, <a>) are supported."></textarea>
                            <div class="invalid-feedback">@{{ errors.content }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─────────────────  SEO  ───────────────── --}}
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">SEO (optional)</h5>
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

            {{-- ─────────────────  Publish  ───────────────── --}}
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Publish</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="status" v-model="form.status">
                        <label class="form-check-label" for="status">
                            @{{ form.status ? 'Published — visible on the site' : 'Draft — hidden from the site' }}
                        </label>
                    </div>

                    <div v-if="serverError" class="alert alert-danger">@{{ serverError }}</div>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success" :disabled="submitting" @click="submitForm">
                            <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                            <span v-if="!submitting"><i class="ti ti-device-floppy me-1"></i></span>
                            Create Post
                        </button>
                        <a href="{{ route('blogs.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            {{-- ─────────────────  Featured Image  ───────────────── --}}
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Featured Image</h5>
                </div>
                <div class="card-body">
                    <input type="file" class="form-control"
                        :class="{ 'is-invalid': errors.image }"
                        id="image" accept="image/jpeg,image/png,image/webp"
                        @change="onImageChange">
                    <div class="invalid-feedback">@{{ errors.image }}</div>
                    <small class="text-muted d-block mt-1">JPEG, PNG, WebP — max 4 MB.</small>

                    <div v-if="imagePreview" class="mt-2">
                        <img :src="imagePreview" alt="Preview" class="img-fluid rounded border">
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('styles')
<style>
    /* Compact spacing for the Add/Edit Blog Post form — scoped to this page only */
    .blogs-form-page { padding-top: 20px; padding-bottom: 20px; }
    .blogs-form-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid #e2e8f0;
    }
    .blogs-form-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        position: relative;
        padding-left: 12px;
    }
    .blogs-form-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0; top: 2px; bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, #1e3a8a, #14b8a6);
    }
    .blogs-form-page .breadcrumb { font-size: 0.75rem; }
    .blogs-form-page .card { border-radius: 10px; box-shadow: none; border: 1px solid #e2e8f0; }
    .blogs-form-page .card-header { padding: 10px 16px; }
    .blogs-form-page .card-title { font-size: 0.9375rem; font-weight: 700; }
    .blogs-form-page .card-body { padding: 16px; }
    .blogs-form-page .header-title { font-size: 1rem; font-weight: 700; }
    .blogs-form-page .mb-3, .blogs-form-page .mb-4 { margin-bottom: 12px !important; }
    .blogs-form-page .form-label { margin-bottom: 4px; font-size: 0.8125rem; font-weight: 600; }
    .blogs-form-page .form-control,
    .blogs-form-page .form-select { padding: 0.4rem 0.65rem; font-size: 0.8125rem; }
    .blogs-form-page small.text-muted { display: inline-block; margin-top: 3px; font-size: 0.75rem; }
    .blogs-form-page .d-flex.justify-content-end.gap-2 { margin-top: 16px !important; }
    .blogs-form-page .form-check { margin-bottom: 2px; }
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
            el: '#blogFormApp',
            data: {
                form: {
                    title: '',
                    slug: '',
                    excerpt: '',
                    content: '',
                    meta_title: '',
                    meta_description: '',
                    status: true,
                },
                slugTouched: false,
                imageFile: null,
                imagePreview: null,
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
                onImageChange(e) {
                    const file = e.target.files[0];
                    if (!file) { this.imageFile = null; this.imagePreview = null; return; }
                    this.imageFile = file;
                    const reader = new FileReader();
                    reader.onload = (ev) => { this.imagePreview = ev.target.result; };
                    reader.readAsDataURL(file);
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

                    const fd = new FormData();
                    Object.keys(this.form).forEach((k) => {
                        const v = this.form[k];
                        if (k === 'status') {
                            fd.append(k, v ? 1 : 0);
                        } else if (v !== null && v !== undefined && v !== '') {
                            fd.append(k, v);
                        }
                    });
                    if (this.imageFile) {
                        fd.append('image', this.imageFile);
                    }

                    try {
                        const res = await fetch('{{ route('blogs.store') }}', {
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

                        const data = await res.json();
                        window.location.href = data.redirect || '{{ route('blogs.index') }}';
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
