@extends('layout.app')

@section('title', 'Edit Category')

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Edit Category</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Paces</a></li>
                <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Categories</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
    </div>

    <div id="categoryEditApp" class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Edit "{{ $category->name }}"</h4>

                    <form id="categoryEditForm" class="needs-validation" novalidate @submit.prevent="submitForm" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        {{-- Parent --}}
                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Parent Category</label>
                            <select
                                class="form-select"
                                id="parent_id"
                                name="parent_id"
                                v-model="form.parent_id"
                                @if ($hasChildren) disabled @endif>
                                <option :value="null">— None (Top-Level Category) —</option>
                                @foreach ($parents as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                            @if ($hasChildren)
                                <small class="text-warning">
                                    <i class="ti ti-alert-triangle me-1"></i>
                                    This category has subcategories, so it cannot itself become a subcategory.
                                </small>
                            @else
                                <small class="text-muted">
                                    Leave blank for top-level. Pick a parent to make this a subcategory.
                                </small>
                            @endif
                        </div>

                        {{-- Name --}}
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                :class="{ 'is-invalid': errors.name, 'is-valid': touched.name && !errors.name && form.name }"
                                id="name"
                                name="name"
                                v-model="form.name"
                                @input="validateField('name')"
                                @blur="touched.name = true"
                                maxlength="150"
                                required>
                            <div class="invalid-feedback" v-if="errors.name">@{{ errors.name }}</div>
                        </div>

                        {{-- Code (read-only per spec) --}}
                        <div class="mb-3">
                            <label for="code" class="form-label">Category Code</label>
                            <input
                                type="text"
                                class="form-control"
                                id="code"
                                :value="form.code"
                                readonly
                                disabled>
                            <small class="text-muted">
                                <i class="ti ti-lock me-1"></i>Category code cannot be changed after creation.
                            </small>
                        </div>

                        {{-- Description --}}
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea
                                class="form-control"
                                :class="{ 'is-invalid': errors.description }"
                                id="description"
                                name="description"
                                v-model="form.description"
                                rows="3"
                                maxlength="1000"></textarea>
                            <div class="invalid-feedback" v-if="errors.description">@{{ errors.description }}</div>
                            <small class="text-muted">@{{ (form.description || '').length }}/1000 characters.</small>
                        </div>

                        {{-- Display Order --}}
                        <div class="mb-3">
                            <label for="display_order" class="form-label">Display Order</label>
                            <input
                                type="number"
                                class="form-control"
                                :class="{ 'is-invalid': errors.display_order }"
                                id="display_order"
                                name="display_order"
                                v-model.number="form.display_order"
                                min="0"
                                max="99999">
                            <div class="invalid-feedback" v-if="errors.display_order">@{{ errors.display_order }}</div>
                        </div>

                        {{-- Image --}}
                        <div class="mb-3">
                            <label class="form-label d-block">Category Image</label>

                            <div v-if="currentImage && !removeImage && !imagePreview" class="mb-2 d-flex align-items-center gap-2">
                                <img :src="currentImage" alt="Current"
                                     class="rounded border"
                                     style="width:120px;height:120px;object-fit:cover;">
                                <button type="button" class="btn btn-sm btn-outline-danger" @click="removeImage = true">
                                    <i class="ti ti-trash me-1"></i>Remove image
                                </button>
                            </div>

                            <div v-if="removeImage && !imagePreview" class="alert alert-warning py-2 px-3 small mb-2">
                                Image will be removed when you save.
                                <a href="#" @click.prevent="removeImage = false">Undo</a>
                            </div>

                            <input
                                type="file"
                                class="form-control"
                                :class="{ 'is-invalid': errors.image }"
                                id="image"
                                name="image"
                                accept="image/jpeg,image/png"
                                @change="onImageChange">
                            <div class="invalid-feedback" v-if="errors.image">@{{ errors.image }}</div>
                            <small class="text-muted">Upload a new image to replace the current one. JPG or PNG, max 2 MB.</small>

                            <div class="mt-2" v-if="imagePreview">
                                <img :src="imagePreview" alt="New preview"
                                     class="rounded border"
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
                                When ticked, products under this category and its subcategories show the Gemstone
                                Details panel on the product form and require carat / stone type / treatment.
                            </small>
                        </div>

                        {{-- Status --}}
                        <div class="mb-3">
                            <label class="form-label d-block">Status <span class="text-danger">*</span></label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="status" v-model="form.status">
                                <label class="form-check-label" for="status">@{{ form.status ? 'Active' : 'Inactive' }}</label>
                            </div>
                        </div>

                        {{-- Server error --}}
                        <div v-if="serverError" class="alert alert-danger" role="alert">@{{ serverError }}</div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('categories.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary" :disabled="submitting">
                                <span v-if="submitting" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                <span v-if="!submitting"><i class="ti ti-device-floppy me-1"></i></span>
                                Update Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="header-title">Audit</h5>
                    <p class="mb-1 text-muted small">
                        <strong>Created:</strong> {{ $category->created_at?->format('d M Y, h:i A') }}
                    </p>
                    <p class="mb-1 text-muted small">
                        <strong>Last Modified:</strong> {{ $category->updated_at?->format('d M Y, h:i A') }}
                    </p>
                    @if ($category->parent)
                        <p class="mb-0 text-muted small">
                            <strong>Parent:</strong>
                            <a href="{{ route('categories.show', $category->parent) }}">{{ $category->parent->name }}</a>
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    new Vue({
        el: '#categoryEditApp',
        data: {
            form: {
                parent_id:     @json($category->parent_id),
                name:          @json($category->name),
                code:          @json($category->code),
                description:   @json($category->description),
                display_order: @json($category->display_order),
                status:        @json((bool) $category->status),
                is_gemstone:   @json((bool) $category->is_gemstone),
            },
            currentImage: @json($category->thumb_url ?? $category->image_url),
            imageFile: null,
            imagePreview: null,
            removeImage: false,
            errors: {},
            touched: {},
            submitting: false,
            serverError: null,
            hasChildren: @json((bool) $hasChildren),
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
                this.removeImage = false;
                const reader = new FileReader();
                reader.onload = (ev) => { this.imagePreview = ev.target.result; };
                reader.readAsDataURL(file);
            },
            validateAll() {
                this.validateField('name');
                return Object.keys(this.errors).length === 0;
            },
            async submitForm() {
                this.serverError = null;
                if (!this.validateAll()) return;
                this.submitting = true;

                const fd = new FormData();
                fd.append('_method', 'PUT');
                fd.append('name', this.form.name);
                fd.append('description', this.form.description || '');
                fd.append('display_order', this.form.display_order || 0);
                fd.append('status', this.form.status ? 1 : 0);
                // parent_id is only sent if the field isn't disabled (no children)
                if (!this.hasChildren) {
                    fd.append('parent_id', this.form.parent_id || '');
                }
                // is_gemstone is only meaningful for top-level categories.
                // Send it whenever the category IS top-level (no parent selected).
                if (!this.form.parent_id) {
                    fd.append('is_gemstone', this.form.is_gemstone ? 1 : 0);
                }
                if (this.imageFile)   fd.append('image', this.imageFile);
                if (this.removeImage) fd.append('remove_image', 1);

                try {
                    const res = await fetch('{{ route('categories.update', $category) }}', {
                        method: 'POST', // _method spoof for PUT
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: fd,
                    });

                    if (res.status === 422) {
                        const data = await res.json();
                        Object.keys(data.errors || {}).forEach((k) => this.$set(this.errors, k, data.errors[k][0]));
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
                    this.serverError = 'Network error. Please try again.';
                    this.submitting = false;
                }
            },
        },
    });
</script>
@endpush
