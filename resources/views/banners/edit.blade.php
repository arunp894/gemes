@extends('layout.app')

@section('title', 'Edit Banner — ' . $banner->title)

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Edit Banner</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('banners.index') }}">Banners</a></li>
                <li class="breadcrumb-item"><a href="{{ route('banners.show', $banner) }}">{{ $banner->title }}</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
    </div>

    <div class="row" id="bannerFormApp">
        <div class="col-12">

            {{-- ─────────────────  Banner Info  ───────────────── --}}
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Banner Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control"
                                :class="{ 'is-invalid': errors.title }"
                                id="title" v-model="form.title" maxlength="191" required>
                            <div class="invalid-feedback">@{{ errors.title }}</div>
                        </div>

                        <div class="col-md-4">
                            <label for="position" class="form-label">Position <span class="text-danger">*</span></label>
                            <select id="position" class="form-select"
                                :class="{ 'is-invalid': errors.position }"
                                v-model="form.position" required>
                                @foreach ($positions as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">@{{ errors.position }}</div>
                        </div>

                        <div class="col-md-8">
                            <label for="subtitle" class="form-label">Subtitle</label>
                            <input type="text" class="form-control"
                                :class="{ 'is-invalid': errors.subtitle }"
                                id="subtitle" v-model="form.subtitle" maxlength="191">
                            <div class="invalid-feedback">@{{ errors.subtitle }}</div>
                        </div>

                        <div class="col-md-2">
                            <label for="sort_order" class="form-label">Sort Order <span class="text-danger">*</span></label>
                            <input type="number" class="form-control"
                                :class="{ 'is-invalid': errors.sort_order }"
                                id="sort_order" v-model="form.sort_order" min="0" max="9999">
                            <div class="invalid-feedback">@{{ errors.sort_order }}</div>
                            <small class="text-muted">Lower = shown first.</small>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label d-block">Status <span class="text-danger">*</span></label>
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" role="switch" id="status"
                                    v-model="form.status">
                                <label class="form-check-label" for="status">
                                    @{{ form.status ? 'Active' : 'Inactive' }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─────────────────  Link  ───────────────── --}}
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Link (optional)</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="link_url" class="form-label">Link URL</label>
                            <input type="text" class="form-control"
                                :class="{ 'is-invalid': errors.link_url }"
                                id="link_url" v-model="form.link_url" maxlength="500">
                            <div class="invalid-feedback">@{{ errors.link_url }}</div>
                        </div>

                        <div class="col-md-4">
                            <label for="link_text" class="form-label">Button / Link Text</label>
                            <input type="text" class="form-control"
                                :class="{ 'is-invalid': errors.link_text }"
                                id="link_text" v-model="form.link_text" maxlength="100">
                            <div class="invalid-feedback">@{{ errors.link_text }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─────────────────  Schedule  ───────────────── --}}
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Schedule (optional)</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="starts_at" class="form-label">Start Date</label>
                            <input type="datetime-local" class="form-control"
                                :class="{ 'is-invalid': errors.starts_at }"
                                id="starts_at" v-model="form.starts_at">
                            <div class="invalid-feedback">@{{ errors.starts_at }}</div>
                        </div>

                        <div class="col-md-6">
                            <label for="ends_at" class="form-label">End Date</label>
                            <input type="datetime-local" class="form-control"
                                :class="{ 'is-invalid': errors.ends_at }"
                                id="ends_at" v-model="form.ends_at">
                            <div class="invalid-feedback">@{{ errors.ends_at }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─────────────────  Image  ───────────────── --}}
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Banner Image</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        {{-- Existing image --}}
                        @if ($banner->hasImage())
                        <div class="col-md-12">
                            <label class="form-label">Current Image</label>
                            <div class="d-flex align-items-center gap-3">
                                <img src="{{ $banner->image_thumb_url }}" alt="{{ $banner->title }}"
                                    class="img-thumbnail rounded"
                                    style="max-height: 120px; object-fit: cover;">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remove_image"
                                        v-model="form.remove_image">
                                    <label class="form-check-label text-danger" for="remove_image">
                                        Remove current image
                                    </label>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="col-md-6">
                            <label for="image" class="form-label">
                                {{ $banner->hasImage() ? 'Replace Image' : 'Upload Image' }}
                            </label>
                            <input type="file" class="form-control"
                                :class="{ 'is-invalid': errors.image }"
                                id="image" accept="image/jpeg,image/png,image/webp,image/gif"
                                @change="onImageChange">
                            <div class="invalid-feedback">@{{ errors.image }}</div>
                            <small class="text-muted">JPEG, PNG, WebP, GIF — max 4 MB.</small>
                        </div>

                        <div class="col-md-6" v-if="imagePreview">
                            <label class="form-label">New Image Preview</label>
                            <div>
                                <img :src="imagePreview" alt="Preview"
                                    class="img-fluid rounded border"
                                    style="max-height: 120px; object-fit: cover;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─────────────────  Notes  ───────────────── --}}
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Notes</h5>
                </div>
                <div class="card-body">
                    <textarea class="form-control" rows="3" id="notes" maxlength="2000"
                        :class="{ 'is-invalid': errors.notes }"
                        v-model="form.notes"
                        placeholder="Internal notes (visible to staff only)"></textarea>
                    <div class="invalid-feedback">@{{ errors.notes }}</div>
                </div>
            </div>

            <div v-if="serverError" class="alert alert-danger">@{{ serverError }}</div>

            <div class="d-flex gap-2 justify-content-end mb-4">
                <a href="{{ route('banners.show', $banner) }}" class="btn btn-light">Cancel</a>
                <button type="button" class="btn btn-primary" :disabled="submitting" @click="submitForm">
                    <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                    Save Changes
                </button>
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
            el: '#bannerFormApp',
            data: {
                form: {
                    title:        @json($banner->title),
                    subtitle:     @json($banner->subtitle ?? ''),
                    link_url:     @json($banner->link_url ?? ''),
                    link_text:    @json($banner->link_text ?? ''),
                    position:     @json($banner->position),
                    sort_order:   @json($banner->sort_order),
                    starts_at:    @json($banner->starts_at ? $banner->starts_at->format('Y-m-d\TH:i') : ''),
                    ends_at:      @json($banner->ends_at   ? $banner->ends_at->format('Y-m-d\TH:i')   : ''),
                    status:       @json((bool) $banner->status),
                    notes:        @json($banner->notes ?? ''),
                    remove_image: false,
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
                    if (!this.form.position)     this.$set(this.errors, 'position', 'Position is required.');
                    const so = parseInt(this.form.sort_order, 10);
                    if (isNaN(so) || so < 0 || so > 9999) this.$set(this.errors, 'sort_order', 'Sort order must be 0–9999.');
                    if (this.form.starts_at && this.form.ends_at && this.form.ends_at < this.form.starts_at) {
                        this.$set(this.errors, 'ends_at', 'End date must be on or after the start date.');
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
                        if (k === 'status') {
                            fd.append(k, v ? 1 : 0);
                        } else if (k === 'remove_image') {
                            fd.append(k, v ? 1 : 0);
                        } else if (v !== null && v !== undefined && v !== '') {
                            fd.append(k, v);
                        }
                    });
                    if (this.imageFile) {
                        fd.append('image', this.imageFile);
                    }

                    try {
                        const res = await fetch('{{ route('banners.update', $banner) }}', {
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
                        window.location.href = data.redirect || '{{ route('banners.index') }}';
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
