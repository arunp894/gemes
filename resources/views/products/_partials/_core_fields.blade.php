{{-- Core product fields: title, sku, cascading category dropdown, descriptions,
     country of origin, status. Used by both create and edit forms via the
     productApp Vue 2 instance defined in the parent page. --}}

<div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0">Core Details</h5></div>
    <div class="card-body">
        <div class="row g-3">

            {{-- Title --}}
            <div class="col-md-8">
                <label for="title" class="form-label">Product Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title"
                    v-model="form.title" maxlength="200"
                    :class="{ 'is-invalid': errors.title }" required>
                <div class="invalid-feedback">@{{ errors.title }}</div>
            </div>

            {{-- SKU --}}
            <div class="col-md-4">
                <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="sku" name="sku"
                    v-model="form.sku" maxlength="80"
                    :class="{ 'is-invalid': errors.sku }" required>
                <div class="invalid-feedback">@{{ errors.sku }}</div>
                <small class="text-muted">Letters, numbers, hyphens, underscores — no spaces.</small>
            </div>

            {{-- Top-level Category --}}
            <div class="col-md-6">
                <label for="top_category_id" class="form-label">Category <span class="text-danger">*</span></label>
                <select id="top_category_id" class="form-select"
                    v-model="form.top_category_id" @change="onTopCategoryChange">
                    <option :value="null">— Select Category —</option>
                    @foreach ($topCategories as $cat)
                        <option value="{{ $cat->id }}"
                            data-code="{{ $cat->code }}"
                            data-gemstone="{{ $cat->is_gemstone ? '1' : '0' }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Subcategory --}}
            <div class="col-md-6">
                <label for="category_id" class="form-label">Subcategory <span class="text-danger">*</span></label>
                <select id="category_id" name="category_id" class="form-select"
                    v-model="form.category_id"
                    :class="{ 'is-invalid': errors.category_id }"
                    :disabled="!form.top_category_id || loadingSubcategories">
                    <option :value="null">
                        @{{ loadingSubcategories ? 'Loading…' : (form.top_category_id ? '— Select Subcategory —' : '— Pick a category first —') }}
                    </option>
                    <option v-for="sub in subcategories" :key="sub.id" :value="sub.id">@{{ sub.name }}</option>
                </select>
                <div class="invalid-feedback">@{{ errors.category_id }}</div>
            </div>

            {{-- Country of Origin --}}
            <div class="col-md-6">
                <label for="country_of_origin" class="form-label">Country of Origin</label>
                <input type="text" class="form-control" id="country_of_origin" name="country_of_origin"
                    v-model="form.country_of_origin" maxlength="100">
            </div>

            {{-- Status --}}
            <div class="col-md-6">
                <label class="form-label d-block">Product Status <span class="text-danger">*</span></label>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" role="switch" name="status" v-model="form.status">
                    <label class="form-check-label" for="status">
                        @{{ form.status ? 'Active — visible on listings' : 'Draft — not listed anywhere' }}
                    </label>
                </div>
            </div>

            {{-- Short Description --}}
            <div class="col-md-12">
                <label for="short_description" class="form-label">Short Description</label>
                <textarea class="form-control" id="short_description" name="short_description"
                    v-model="form.short_description" rows="2" maxlength="500"
                    placeholder="Brief listing-preview description (max 500 characters)..."></textarea>
                <small class="text-muted"><span v-text="(form.short_description || '').length"></span> / 500</small>
            </div>

            {{-- Full Description --}}
            <div class="col-md-12">
                <label for="full_description" class="form-label">Full Description</label>
                <textarea class="form-control" id="full_description" name="full_description"
                    v-model="form.full_description" rows="5"
                    placeholder="Detailed product description used on the full product page..."></textarea>
            </div>

            {{-- Notes / Tags --}}
            <div class="col-md-12">
                <label for="notes_tags" class="form-label">Notes / Tags</label>
                <input type="text" class="form-control" id="notes_tags" name="notes_tags"
                    v-model="form.notes_tags" maxlength="1000"
                    placeholder="Internal tags or notes, comma-separated (e.g. premium, holiday, rare)">
                <small class="text-muted">Used for internal search and filtering.</small>
            </div>
        </div>
    </div>
</div>

{{-- Images --}}
<div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0">Images</h5></div>
    <div class="card-body">
        <div class="row g-3">

            {{-- Primary Image --}}
            <div class="col-md-6">
                <label for="primary_image" class="form-label">
                    Primary Image @if (!$product) <span class="text-danger">*</span> @endif
                </label>
                <input type="file" class="form-control" id="primary_image" name="primary_image"
                    accept="image/jpeg,image/png" @change="onPrimaryImageChange">
                <small class="text-muted">JPG or PNG, max 5 MB. Required to enable website visibility.</small>

                <div v-if="primaryImagePreview || existingPrimaryImage" class="mt-2">
                    <img :src="primaryImagePreview || existingPrimaryImage"
                        alt="Primary preview" class="img-fluid rounded border"
                        style="max-height: 150px;">
                    @if ($product)
                        <div class="form-check mt-2" v-if="existingPrimaryImage && !primaryImagePreview">
                            <input class="form-check-input" type="checkbox" id="remove_primary_image"
                                name="remove_primary_image" value="1" v-model="form.remove_primary_image">
                            <label class="form-check-label text-danger" for="remove_primary_image">
                                Remove primary image
                            </label>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Gallery Images --}}
            <div class="col-md-6">
                <label for="gallery_images" class="form-label">Gallery Images</label>
                <input type="file" class="form-control" id="gallery_images" name="gallery_images[]"
                    accept="image/jpeg,image/png" multiple @change="onGalleryImageChange">
                <small class="text-muted">
                    Up to <strong>10</strong> images, JPG/PNG, max 5 MB each.
                    @if ($product)
                        Currently uploaded: @{{ existingGallery.length }}.
                    @endif
                </small>

                {{-- Existing gallery images (edit only) --}}
                @if ($product)
                    <div v-if="existingGallery.length" class="mt-2 d-flex flex-wrap gap-2">
                        <div v-for="g in existingGallery" :key="g.id" class="position-relative">
                            <img :src="g.thumb" alt="" class="rounded border"
                                style="width: 72px; height: 72px; object-fit: cover;">
                            <button type="button"
                                class="btn btn-sm btn-danger position-absolute top-0 end-0 p-0"
                                style="width:20px; height:20px; line-height:1;"
                                @click="removeGalleryImage(g.id)" title="Remove">
                                <i class="ti ti-x fs-12"></i>
                            </button>
                        </div>
                    </div>
                @endif

                {{-- New gallery image previews --}}
                <div v-if="galleryPreviews.length" class="mt-2 d-flex flex-wrap gap-2">
                    <div v-for="(p, idx) in galleryPreviews" :key="'new-' + idx" class="position-relative">
                        <img :src="p" alt="" class="rounded border"
                            style="width: 72px; height: 72px; object-fit: cover; opacity: 0.85;">
                        <span class="badge bg-success position-absolute top-0 start-0">New</span>
                    </div>
                </div>

                {{-- Hidden inputs to track gallery removals --}}
                <template v-for="rid in form.remove_gallery_ids">
                    <input type="hidden" name="remove_gallery_ids[]" :value="rid" :key="'rmg-' + rid">
                </template>
            </div>
        </div>
    </div>
</div>
