@extends('layout.app')

@section('title', 'Edit Blog Post')

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Edit Blog Post</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('blogs.index') }}">Blog</a></li>
                <li class="breadcrumb-item"><a href="{{ route('blogs.show', $blog) }}">{{ $blog->title }}</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
    </div>

    <div class="row" id="blogFormApp">
        <div class="col-xl-8">

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
                                id="title" v-model="form.title" maxlength="191" required>
                            <div class="invalid-feedback">@{{ errors.title }}</div>
                        </div>

                        <div class="col-md-12">
                            <label for="slug" class="form-label">Slug</label>
                            <input type="text" class="form-control"
                                :class="{ 'is-invalid': errors.slug }"
                                id="slug" v-model="form.slug" maxlength="191">
                            <div class="invalid-feedback">@{{ errors.slug }}</div>
                            <small class="text-muted">URL: /blog/@{{ form.slug || '…' }}</small>
                        </div>

                        <div class="col-md-12">
                            <label for="excerpt" class="form-label">Excerpt</label>
                            <textarea class="form-control" rows="2" id="excerpt" maxlength="500"
                                :class="{ 'is-invalid': errors.excerpt }"
                                v-model="form.excerpt"></textarea>
                            <div class="invalid-feedback">@{{ errors.excerpt }}</div>
                        </div>

                        <div class="col-md-12">
                            <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" rows="14" id="content"
                                :class="{ 'is-invalid': errors.content }"
                                v-model="form.content"></textarea>
                            <div class="invalid-feedback">@{{ errors.content }}</div>
                        </div>
                    </div>
                </div>
            </div>

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
                        <button type="button" class="btn btn-primary" :disabled="submitting" @click="submitForm">
                            <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                            Save Changes
                        </button>
                        <a href="{{ route('blogs.show', $blog) }}" class="btn btn-light">Cancel</a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Featured Image</h5>
                </div>
                <div class="card-body">

                    @if ($blog->hasImage())
                        <div class="mb-3">
                            <img src="{{ $blog->image_thumb_url }}" alt="{{ $blog->title }}"
                                class="img-fluid rounded border mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remove_image"
                                    v-model="form.remove_image">
                                <label class="form-check-label text-danger" for="remove_image">
                                    Remove current image
                                </label>
                            </div>
                        </div>
                    @endif

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

@push('scripts')
<script>
    $(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        new Vue({
            el: '#blogFormApp',
            data: {
                form: {
                    title:             @json($blog->title),
                    slug:              @json($blog->slug),
                    excerpt:           @json($blog->excerpt ?? ''),
                    content:           @json($blog->content),
                    meta_title:        @json($blog->meta_title ?? ''),
                    meta_description:  @json($blog->meta_description ?? ''),
                    status:            @json((bool) $blog->status),
                    remove_image:      false,
                },
                imageFile: null,
                imagePreview: null,
                errors: {},
                submitting: false,
                serverError: null,
            },
            methods: {
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
                    fd.append('_method', 'PUT');
                    Object.keys(this.form).forEach((k) => {
                        const v = this.form[k];
                        if (k === 'status' || k === 'remove_image') {
                            fd.append(k, v ? 1 : 0);
                        } else if (v !== null && v !== undefined && v !== '') {
                            fd.append(k, v);
                        }
                    });
                    if (this.imageFile) {
                        fd.append('image', this.imageFile);
                    }

                    try {
                        const res = await fetch('{{ route('blogs.update', $blog) }}', {
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
