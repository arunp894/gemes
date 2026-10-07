{{-- Gemstone-specific fields panel. Shown only when the chosen category has
     is_gemstone = true. Visibility is controlled by `isGemstone` on the
     productApp Vue instance. --}}

<div class="card mb-3" v-show="isGemstone">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ti ti-diamond me-1"></i>Gemstone Details</h5>
        <span class="badge badge-soft-info">Carat Weight required for gemstone products</span>
    </div>
    <div class="card-body">
        <div class="row g-3">

            {{-- Carat Weight --}}
            <div class="col-md-4">
                <label for="carat_weight" class="form-label">Carat Weight <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" step="0.001" min="0.001" class="form-control" id="carat_weight"
                        name="carat_weight" v-model="form.carat_weight"
                        :class="{ 'is-invalid': errors.carat_weight }" placeholder="2.350">
                    <span class="input-group-text">ct</span>
                    <div class="invalid-feedback">@{{ errors.carat_weight }}</div>
                </div>
            </div>

            {{-- Stone Type is hidden on this form — still set via the Purchase
                 intake flow (see PurchaseService::syncLines()), just not
                 editable here. form.stone_type is left untouched so an
                 existing value round-trips unchanged on save. --}}

            @php
                // Options come from the Treatment / Shape / Colour / Clarity masters.
                // An inactive master the product already uses stays selectable.
                $attrFields = [
                    ['treatment_id', 'Treatment',      \App\Models\Treatment::class],
                    ['shape_id',     'Cut / Shape',    \App\Models\Shape::class],
                    ['color_id',     'Colour',         \App\Models\Color::class],
                    ['clarity_id',   'Clarity',        \App\Models\Clarity::class],
                ];
            @endphp
            @foreach ($attrFields as [$field, $label, $model])
                @php
                    $options = $model::active()->ordered()->get(['id', 'name']);
                    $current = $product->{$field} ?? null;
                    if ($current && ! $options->contains('id', $current)) {
                        $options->push($model::withTrashed()->find($current, ['id', 'name']));
                    }
                @endphp
                <div class="col-md-4">
                    <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                    <select class="form-select" id="{{ $field }}" name="{{ $field }}"
                        v-model.number="form.{{ $field }}" :class="{ 'is-invalid': errors.{{ $field }} }">
                        <option :value="null">— Select —</option>
                        @foreach ($options->filter() as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback" v-text="errors.{{ $field }}"></div>
                </div>
            @endforeach

            {{-- Certificate Number --}}
            <div class="col-md-4">
                <label for="certificate_number" class="form-label">Certificate Number</label>
                <input type="text" class="form-control" id="certificate_number" name="certificate_number"
                    v-model="form.certificate_number" maxlength="100"
                    placeholder="GIA / IGI / AGL ref">
            </div>

            {{-- Stone Description --}}
            <div class="col-md-12">
                <label for="stone_description" class="form-label">Stone Description</label>
                <textarea class="form-control" id="stone_description" name="stone_description"
                    v-model="form.stone_description" rows="3"
                    placeholder="Additional grading notes, inclusions, brilliance, provenance, etc. (optional)"></textarea>
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
