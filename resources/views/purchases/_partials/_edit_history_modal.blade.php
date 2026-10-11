{{-- "Edit History" popup for a purchase: one card per edit — who, when, and exactly what changed.
     Needs: $purchase, $editLogs (PurchaseEditLog collection with editor), $locationNames (id => name). --}}
@php
    $fieldLabels = [
        'qty' => 'Pieces', 'carat_weight' => 'Carat', 'price' => 'Price', 'website_price' => 'Selling price',
        'website_enabled' => 'Website', 'rack_id' => 'Rack',
        'purchase_date' => 'Purchase date', 'location_id' => 'Location', 'tax_type' => 'Tax type', 'note' => 'Note',
    ];
    $fmt = function (string $field, $v) use ($settings, $locationNames) {
        if ($v === null || $v === '') return '—';
        return match ($field) {
            'price', 'website_price' => $settings->formatMoney($v, 2),
            'carat_weight'           => rtrim(rtrim(number_format((float) $v, 3), '0'), '.') . ' ct',
            'website_enabled'        => $v ? 'Listed' : 'Not listed',
            'location_id'            => $locationNames[$v] ?? ('#' . $v),
            'purchase_date'          => \Carbon\Carbon::parse($v)->format('d M Y'),
            'tax_type'               => strtoupper(str_replace('_', ' + ', (string) $v)),
            default                  => (string) $v,
        };
    };
    $changeBadge = ['added' => ['Added', 'success'], 'removed' => ['Removed', 'danger'], 'updated' => ['Changed', 'primary']];
@endphp

<div class="modal fade" id="purchaseEditHistoryModal" tabindex="-1" aria-labelledby="purchaseEditHistoryTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content peh">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <span class="peh-icon"><i class="ti ti-history"></i></span>
                    <div>
                        <h5 class="modal-title mb-0" id="purchaseEditHistoryTitle">Edit History</h5>
                        <small class="text-muted">{{ $purchase->invoice_number }}
                            &middot; {{ $editLogs->count() }} {{ \Illuminate\Support\Str::plural('edit', $editLogs->count()) }}</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="peh-note">
                    <i class="ti ti-info-circle"></i>
                    <span>This is an audit log only. The stock entries an edit writes are bookkeeping and are
                        <strong>not</strong> counted as stock removed or stock out in the stock reports.</span>
                </div>

                @forelse ($editLogs as $log)
                    @php
                        $c      = $log->changes ?? [];
                        $header = $c['header'] ?? [];
                        $rows   = $c['rows'] ?? [];
                        $stock  = $c['stock'] ?? [];
                        $mism   = $c['ledger_mismatches'] ?? [];
                        $count  = count($header) + collect($rows)->sum(fn ($r) => max(1, count($r['fields'] ?? [])));
                        $name   = $log->editor?->name ?? 'Unknown user';
                    @endphp
                    <div class="peh-entry">
                        <div class="peh-entry-head">
                            <span class="peh-avatar">{{ strtoupper(mb_substr($name, 0, 1)) }}</span>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $name }}</div>
                                <small class="text-muted">
                                    {{ $log->created_at->format('d M Y, h:i A') }}
                                    <span class="mx-1">&middot;</span>{{ $log->created_at->diffForHumans() }}
                                </small>
                            </div>
                            <span class="badge {{ $count ? 'badge-soft-primary' : 'badge-soft-secondary' }}">
                                {{ $count ? $count . ' ' . \Illuminate\Support\Str::plural('change', $count) : 'No field changes' }}
                            </span>
                        </div>

                        @if ($header)
                            <div class="peh-section">Purchase details</div>
                            @foreach ($header as $field => $d)
                                <div class="peh-line">
                                    <span class="peh-field">{{ $fieldLabels[$field] ?? ucfirst($field) }}</span>
                                    <span class="peh-from">{{ $fmt($field, $d['from']) }}</span>
                                    <i class="ti ti-arrow-right text-muted"></i>
                                    <span class="peh-to">{{ $fmt($field, $d['to']) }}</span>
                                </div>
                            @endforeach
                        @endif

                        @if ($rows)
                            <div class="peh-section">Items</div>
                            @foreach ($rows as $r)
                                @php [$bLabel, $bColor] = $changeBadge[$r['change']] ?? ['Changed', 'secondary']; @endphp
                                <div class="peh-item">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge badge-soft-{{ $bColor }}">{{ $bLabel }}</span>
                                        <span class="fw-semibold">{{ $r['line'] ?? 'Item' }}</span>
                                        @if (! empty($r['lot']))<code class="small">{{ $r['lot'] }}</code>@endif
                                    </div>
                                    @if ($r['change'] === 'updated')
                                        @foreach ($r['fields'] as $field => $d)
                                            <div class="peh-line">
                                                <span class="peh-field">{{ $fieldLabels[$field] ?? ucfirst($field) }}</span>
                                                <span class="peh-from">{{ $fmt($field, $d['from']) }}</span>
                                                <i class="ti ti-arrow-right text-muted"></i>
                                                <span class="peh-to">{{ $fmt($field, $d['to']) }}</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="small text-muted">
                                            @foreach ($r['fields'] as $field => $v)
                                                {{ $fieldLabels[$field] ?? $field }}: <strong>{{ $fmt($field, $v) }}</strong>@if (! $loop->last) &middot; @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @endif

                        @if ($stock)
                            <div class="peh-section">Pieces on hand</div>
                            @foreach ($stock as $s)
                                <div class="peh-line">
                                    <span class="peh-field">{{ $s['line'] }}@if (! empty($s['lot'])) <code class="small">{{ $s['lot'] }}</code>@endif</span>
                                    <span class="peh-from">{{ $s['from_qty'] }} pcs</span>
                                    <i class="ti ti-arrow-right text-muted"></i>
                                    <span class="peh-to">{{ $s['to_qty'] }} pcs</span>
                                </div>
                            @endforeach
                        @endif

                        @if ($mism)
                            <div class="peh-warn">
                                <i class="ti ti-alert-triangle"></i>
                                <div>
                                    <strong>Stock mismatch noticed before this edit</strong>
                                    @foreach ($mism as $m)
                                        <div class="small">{{ $m['line'] }}@if (! empty($m['lot'])) ({{ $m['lot'] }})@endif:
                                            purchase says {{ $m['recorded_qty'] }}, stock ledger had {{ $m['ledger_on_hand'] }}.</div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (! $count && ! $stock)
                            <div class="small text-muted mt-2">Saved without changing any field.</div>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-muted py-5">
                        <i class="ti ti-history fs-1 d-block mb-2"></i>
                        No edits recorded yet.<br>
                        <small>Changes made to this purchase from now on will appear here.</small>
                    </div>
                @endforelse
            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .peh .peh-icon { width: 40px; height: 40px; border-radius: 12px; display: inline-flex; align-items: center;
        justify-content: center; background: #eff6ff; color: #1d4ed8; font-size: 1.25rem; }
    .peh .peh-note { display: flex; gap: 8px; align-items: flex-start; background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 10px; padding: 10px 12px; font-size: .8rem; color: #475569; margin-bottom: 14px; }
    .peh .peh-note i { color: #1d4ed8; font-size: 1rem; margin-top: 1px; }
    .peh .peh-entry { border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; margin-bottom: 12px; background: #fff; }
    .peh .peh-entry-head { display: flex; align-items: center; gap: 12px; margin-bottom: 4px; }
    .peh .peh-avatar { width: 34px; height: 34px; border-radius: 50%; background: #1d4ed8; color: #fff; font-weight: 600;
        display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .peh .peh-section { margin: 12px 0 6px; font-size: .68rem; font-weight: 700; letter-spacing: .08em;
        text-transform: uppercase; color: #94a3b8; }
    .peh .peh-item { background: #f8fafc; border-radius: 8px; padding: 8px 10px; margin-bottom: 6px; }
    .peh .peh-line { display: flex; align-items: center; gap: 8px; font-size: .84rem; padding: 2px 0; flex-wrap: wrap; }
    .peh .peh-field { min-width: 110px; color: #64748b; }
    .peh .peh-from { color: #b91c1c; text-decoration: line-through; text-decoration-color: rgba(185, 28, 28, .45); }
    .peh .peh-to { color: #15803d; font-weight: 600; }
    .peh .peh-warn { display: flex; gap: 8px; margin-top: 12px; padding: 8px 10px; border-radius: 8px;
        background: #fffbeb; border: 1px solid #fde68a; color: #92400e; font-size: .82rem; }
    .peh .peh-warn i { margin-top: 2px; }
    @media print { #purchaseEditHistoryModal { display: none !important; } }
</style>
@endpush
