{{-- Multi-Barcode Panel.
     Supports both Single-Barcode and Multi-Barcode modes. The "mode" is
     a Vue-only UI concept — server side, both modes just mean N rows of
     barcodes (1 row = single, 2..20 = multi).

     This partial mounts inside the productApp Vue instance — all `form.*`
     and `barcodes` data live on that root instance.
--}}

<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ti ti-barcode me-1"></i>Barcodes</h5>
        <div class="btn-group btn-group-sm" role="group" aria-label="Barcode mode">
            <button type="button" class="btn"
                :class="barcodeMode === 'single' ? 'btn-primary' : 'btn-light'"
                @click="setBarcodeMode('single')">Single</button>
            <button type="button" class="btn"
                :class="barcodeMode === 'multi' ? 'btn-primary' : 'btn-light'"
                @click="setBarcodeMode('multi')">Multi</button>
        </div>
    </div>
    <div class="card-body">

        <p class="text-muted small mb-3" v-if="barcodeMode === 'single'">
            <i class="ti ti-info-circle me-1"></i>
            Single-Barcode mode: one barcode per product. Standard for most retail items.
        </p>
        <p class="text-muted small mb-3" v-else>
            <i class="ti ti-info-circle me-1"></i>
            Multi-Barcode mode: up to <strong>20</strong> barcodes per product, used when
            the item carries different identifiers across channels.
        </p>

        {{-- Barcode list --}}
        <div v-for="(barcode, idx) in barcodes" :key="barcode._uid" class="barcode-row border rounded p-3 mb-2">
            <div class="row g-2 align-items-end">
                {{-- Sequence # + Primary --}}
                <div class="col-md-1">
                    <label class="form-label small text-muted">#</label>
                    <div class="d-flex align-items-center gap-1">
                        <span class="fw-bold">@{{ idx + 1 }}</span>
                        <button type="button" class="btn btn-sm p-0"
                            @click="setPrimary(idx)"
                            :title="barcode.is_primary ? 'Primary barcode' : 'Mark as primary'">
                            <i :class="barcode.is_primary
                                ? 'ti ti-star-filled text-warning'
                                : 'ti ti-star text-muted'"
                               class="fs-lg"></i>
                        </button>
                    </div>
                </div>

                {{-- Format --}}
                <div class="col-md-2">
                    <label class="form-label small">Format <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" v-model="barcode.format"
                        @change="onFormatChange(idx)">
                        @foreach (\App\Models\Barcode::FORMATS as $format)
                            <option value="{{ $format }}">{{ $format }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Value --}}
                <div class="col-md-3">
                    <label class="form-label small">Value <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control"
                            :class="{ 'is-invalid': barcode.error, 'is-valid': barcode.validated && !barcode.error }"
                            v-model="barcode.value"
                            @input="onValueInput(idx)"
                            placeholder="Barcode value">
                        <button type="button" class="btn btn-light"
                            v-if="barcode.format === '{{ \App\Models\Barcode::FORMAT_EAN_13 }}'"
                            @click="autoGenerate(idx)" title="Auto-generate EAN-13">
                            <i class="ti ti-refresh"></i>
                        </button>
                    </div>
                    <div v-if="barcode.error" class="invalid-feedback d-block">
                        @{{ barcode.error }}
                    </div>
                </div>

                {{-- Label --}}
                <div class="col-md-2">
                    <label class="form-label small">Label</label>
                    <input type="text" class="form-control form-control-sm"
                        v-model="barcode.label" maxlength="100"
                        placeholder="e.g. eBay, Retail">
                </div>

                {{-- Channels --}}
                <div class="col-md-3">
                    <label class="form-label small">Channels</label>
                    <select class="form-select form-select-sm" multiple
                        v-model="barcode.channel_ids" size="2"
                        title="Leave empty to use across all channels">
                        @foreach ($channels as $channel)
                            <option value="{{ $channel->id }}">{{ $channel->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Empty = all channels</small>
                </div>

                {{-- Remove --}}
                <div class="col-md-1 text-end">
                    <label class="form-label small d-block">&nbsp;</label>
                    <button type="button" class="btn btn-sm btn-light text-danger"
                        @click="removeBarcode(idx)"
                        :disabled="barcodes.length <= 1" title="Remove">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Add button (only in multi mode, and respects max limit) --}}
        <button type="button" class="btn btn-sm btn-light w-100 mt-2"
            v-if="barcodeMode === 'multi' && barcodes.length < {{ \App\Models\Barcode::MAX_BARCODES_PER_PRODUCT }}"
            @click="addBarcode">
            <i class="ti ti-plus me-1"></i>Add Barcode
            <small class="text-muted">(@{{ barcodes.length }} / {{ \App\Models\Barcode::MAX_BARCODES_PER_PRODUCT }})</small>
        </button>

        <p v-if="barcodesError" class="alert alert-danger mt-2 mb-0 small">
            @{{ barcodesError }}
        </p>
    </div>
</div>
