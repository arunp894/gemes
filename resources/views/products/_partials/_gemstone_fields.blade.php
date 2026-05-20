{{-- Gemstone-specific fields panel. Shown only when the chosen subcategory's
     parent code is in the gemstone group (see Product::GEMSTONE_PARENT_CODES).
     Visibility is controlled by `isGemstone` on the productApp Vue instance. --}}

<div class="card mb-3" v-show="isGemstone">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ti ti-diamond me-1"></i>Gemstone Details</h5>
        <span class="badge badge-soft-info">Required for gemstone products</span>
    </div>
    <div class="card-body">
        <div class="row g-3">

            {{-- Carat Weight --}}
            <div class="col-md-3">
                <label for="carat_weight" class="form-label">Carat Weight <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" step="0.001" min="0.001" class="form-control" id="carat_weight"
                        name="carat_weight" v-model="form.carat_weight"
                        :class="{ 'is-invalid': errors.carat_weight }" placeholder="2.350">
                    <span class="input-group-text">ct</span>
                    <div class="invalid-feedback">@{{ errors.carat_weight }}</div>
                </div>
            </div>

            {{-- Stone Type --}}
            <div class="col-md-3">
                <label for="stone_type" class="form-label">Stone Type <span class="text-danger">*</span></label>
                <select class="form-select" id="stone_type" name="stone_type"
                    v-model="form.stone_type" :class="{ 'is-invalid': errors.stone_type }">
                    <option :value="null">— Select —</option>
                    @foreach (\App\Models\Product::STONE_TYPES as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback">@{{ errors.stone_type }}</div>
            </div>

            {{-- Treatment --}}
            <div class="col-md-3">
                <label for="treatment" class="form-label">Treatment <span class="text-danger">*</span></label>
                <select class="form-select" id="treatment" name="treatment"
                    v-model="form.treatment" :class="{ 'is-invalid': errors.treatment }">
                    <option :value="null">— Select —</option>
                    @foreach (\App\Models\Product::TREATMENTS as $treatment)
                        <option value="{{ $treatment }}">{{ $treatment }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback">@{{ errors.treatment }}</div>
            </div>

            {{-- Cut / Shape --}}
            <div class="col-md-3">
                <label for="cut_shape" class="form-label">Cut / Shape</label>
                <select class="form-select" id="cut_shape" name="cut_shape" v-model="form.cut_shape">
                    <option :value="null">— Select —</option>
                    @foreach (\App\Models\Product::CUT_SHAPES as $shape)
                        <option value="{{ $shape }}">{{ $shape }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Colour Grade --}}
            <div class="col-md-4">
                <label for="colour_grade" class="form-label">Colour Grade</label>
                <input type="text" class="form-control" id="colour_grade" name="colour_grade"
                    v-model="form.colour_grade" maxlength="100"
                    placeholder="e.g. Vivid Red, Deep Blue">
            </div>

            {{-- Clarity Grade --}}
            <div class="col-md-4">
                <label for="clarity_grade" class="form-label">Clarity Grade</label>
                <select class="form-select" id="clarity_grade" name="clarity_grade" v-model="form.clarity_grade">
                    <option :value="null">— Select —</option>
                    @foreach (\App\Models\Product::CLARITY_GRADES as $clarity)
                        <option value="{{ $clarity }}">{{ $clarity }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Certificate Number --}}
            <div class="col-md-4">
                <label for="certificate_number" class="form-label">Certificate Number</label>
                <input type="text" class="form-control" id="certificate_number" name="certificate_number"
                    v-model="form.certificate_number" maxlength="100"
                    placeholder="GIA / IGI / AGL ref">
            </div>

            {{-- Certificate Image / PDF --}}
            <div class="col-md-12">
                <label for="certificate_image" class="form-label">Certificate Document</label>
                <input type="file" class="form-control" id="certificate_image" name="certificate_image"
                    accept="image/jpeg,image/png,application/pdf" @change="onCertificateChange">
                <small class="text-muted">JPG, PNG, or PDF, max 10 MB.</small>

                <div v-if="certificatePreview || existingCertificate" class="mt-2">
                    @if (true)
                        <a v-if="existingCertificate && !certificatePreview"
                            :href="existingCertificate" target="_blank"
                            class="btn btn-sm btn-light">
                            <i class="ti ti-file me-1"></i>View current certificate
                        </a>
                        <span v-if="certificatePreview" class="badge bg-success">New file selected</span>

                        @if ($product)
                            <div class="form-check mt-2" v-if="existingCertificate && !certificatePreview">
                                <input class="form-check-input" type="checkbox"
                                    id="remove_certificate_image" name="remove_certificate_image" value="1"
                                    v-model="form.remove_certificate_image">
                                <label class="form-check-label text-danger" for="remove_certificate_image">
                                    Remove certificate
                                </label>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
