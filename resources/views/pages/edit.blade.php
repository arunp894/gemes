@extends('layout.app')

@section('title', 'Edit Page')

@section('content')

<div class="container-fluid pages-page pages-form-page">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Edit Page</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('pages.index') }}">Pages</a></li>
                <li class="breadcrumb-item active">{{ $page->title }}</li>
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
                                id="title" v-model="form.title" maxlength="191" required>
                            <div class="invalid-feedback">@{{ errors.title }}</div>
                        </div>

                        <div class="col-md-12">
                            <label for="slug" class="form-label">Slug</label>
                            <input type="text" class="form-control"
                                :class="{ 'is-invalid': errors.slug }"
                                id="slug" v-model="form.slug" maxlength="191">
                            <div class="invalid-feedback">@{{ errors.slug }}</div>
                            <small class="text-muted">URL: /pages/@{{ form.slug || '…' }} — changing this will break any existing links to the old URL.</small>
                        </div>

                        <div class="col-md-12">
                            <label for="content-editor" class="form-label">Content <span class="text-danger">*</span></label>
                            <div id="content-editor" class="pages-editor" :class="{ 'is-invalid': errors.content }"></div>
                            <div class="invalid-feedback" :class="{ 'd-block': errors.content }">@{{ errors.content }}</div>
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
                    <a href="{{ route('website.pages.show', $page) }}" target="_blank" class="btn btn-soft-secondary btn-sm mb-3 w-100">
                        <i class="ti ti-external-link me-1"></i> View on Site
                    </a>

                    <div v-if="serverError" class="alert alert-danger">@{{ serverError }}</div>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary" :disabled="submitting" @click="submitForm">
                            <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                            <span v-if="!submitting"><i class="ti ti-device-floppy me-1"></i></span>
                            Save Changes
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
<link rel="stylesheet" href="{{ asset('assets/plugins/quill/quill.snow.css') }}">
<style>
    /* Compact spacing for the Add/Edit Page form — scoped to this page only */
    .pages-form-page .pages-editor .ql-container { min-height: 320px; font-size: 0.875rem; border-bottom-left-radius: 4px; border-bottom-right-radius: 4px; }
    .pages-form-page .pages-editor .ql-toolbar { border-top-left-radius: 4px; border-top-right-radius: 4px; }
    .pages-form-page .pages-editor.is-invalid .ql-toolbar,
    .pages-form-page .pages-editor.is-invalid .ql-container { border-color: #dc3545; }
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
<script src="{{ asset('assets/plugins/quill/quill.js') }}"></script>
<script>
    $(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        new Vue({
            el: '#pageFormApp',
            data: {
                form: {
                    title:             @json($page->title),
                    slug:              @json($page->slug),
                    content:           @json($page->content),
                    meta_title:        @json($page->meta_title ?? ''),
                    meta_description:  @json($page->meta_description ?? ''),
                },
                errors: {},
                submitting: false,
                serverError: null,
            },
            mounted() {
                this.quill = new Quill('#content-editor', {
                    theme: 'snow',
                    placeholder: 'Write the page content here…',
                    modules: {
                        toolbar: {
                            container: [
                                [{ header: [false, 1, 2, 3] }],
                                ['bold', 'italic', 'underline', 'strike'],
                                [{ color: [] }, { background: [] }],
                                ['blockquote', 'code-block'],
                                [{ list: 'ordered' }, { list: 'bullet' }],
                                [{ align: [] }],
                                ['link', 'image'],
                                ['clean'],
                            ],
                            handlers: { image: () => this.pickEditorImage() },
                        },
                    },
                });
                this.quill.root.innerHTML = this.form.content;
                this.quill.on('text-change', () => {
                    this.form.content = this.quill.root.innerHTML;
                    if (this.errors.content) this.$delete(this.errors, 'content');
                });
            },
            methods: {
                pickEditorImage() {
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.accept = 'image/jpeg,image/png,image/webp';
                    input.onchange = async () => {
                        const file = input.files[0];
                        if (!file) return;

                        const range = this.quill.getSelection(true);
                        const fd = new FormData();
                        fd.append('image', file);

                        try {
                            const res = await fetch('{{ route('pages.upload-image') }}', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                                body: fd,
                            });
                            const data = await res.json();
                            if (!res.ok || !data.url) {
                                alert(data.message || 'Image upload failed.');
                                return;
                            }
                            this.quill.insertEmbed(range.index, 'image', data.url, 'user');
                            this.quill.setSelection(range.index + 1);
                        } catch (err) {
                            alert('Network error while uploading the image.');
                        }
                    };
                    input.click();
                },
                validateLocal() {
                    this.errors = {};
                    if (!this.form.title.trim()) this.$set(this.errors, 'title', 'Title is required.');
                    if (!this.quill.getText().trim()) this.$set(this.errors, 'content', 'Content is required.');
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
                        const res = await fetch('{{ route('pages.update', $page) }}', {
                            method: 'PUT',
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
