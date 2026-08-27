<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Missing Stock — {{ $audit->audit_number }}</title>
    <style>
        {{--
            dompdf only renders a subset of CSS (no flexbox/grid, spotty
            custom-property support) so this stays static hex values,
            table/inline-block layout — matching the blue brand identity
            used across the rest of the Stock Audits module without
            relying on anything dompdf can't paint.
        --}}
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #1e293b; }
        h1 { font-size: 17px; margin: 0 0 4px; color: #1e293b; }
        .muted { color: #64748b; }

        .accent-bar { height: 4px; background: #1d4ed8; margin-bottom: 10px; border-radius: 2px; }

        .header-table { width: 100%; margin-bottom: 12px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .header-table td { vertical-align: top; padding: 0; }
        .text-right { text-align: right; }

        .status-badge {
            display: inline-block; padding: 2px 9px; border-radius: 9px;
            font-size: 9px; font-weight: bold; margin-left: 4px;
        }
        .status-in-progress { background: #eff6ff; color: #1d4ed8; }
        .status-completed   { background: #ecfdf5; color: #059669; }
        .status-cancelled   { background: #fef2f2; color: #dc2626; }

        .summary-row { width: 100%; margin: 10px 0 4px; }
        .summary-chip {
            display: inline-block; padding: 5px 12px; border-radius: 6px;
            font-size: 10px; margin-right: 8px; border: 1px solid #e2e8f0;
        }
        .summary-chip strong { font-size: 12px; }
        .summary-chip-total    { background: #f8fafc; color: #1e293b; }
        .summary-chip-matched  { background: #ecfdf5; color: #059669; border-color: #a7f3d0; }
        .summary-chip-missing  { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

        table.report { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.report th {
            background: #eff6ff; color: #1e3a8a; text-align: left; padding: 6px;
            font-size: 9px; text-transform: uppercase; border-bottom: 1px solid #bfdbfe;
        }
        table.report td { padding: 5px 6px; border-bottom: 1px solid #eceef1; font-size: 9.5px; }
        table.report tr:nth-child(even) { background: #fafafa; }

        .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 8px; background: #fffbeb; color: #d97706; }
        .empty-row { text-align: center; color: #9ca3af; padding: 20px 0; }

        .footer { margin-top: 14px; padding-top: 6px; border-top: 2px solid #e2e8f0; font-size: 8px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="accent-bar"></div>

    <table class="header-table">
        <tr>
            <td>
                <h1>Missing Stock Report</h1>
                <div class="muted">Audit {{ $audit->audit_number }} &middot; {{ $audit->location?->name }}</div>
            </td>
            <td class="text-right">
                <div>
                    <strong>Status:</strong>
                    <span class="status-badge status-{{ str_replace('_', '-', $audit->status) }}">{{ $audit->statusLabel() }}</span>
                </div>
                <div><strong>Audit Date:</strong> {{ optional($audit->audit_date)->format('d M Y') }}</div>
                <div><strong>Stone:</strong> {{ $audit->categoryLabel() }}</div>
                <div><strong>Generated:</strong> {{ $generatedAt->format('d M Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <div class="summary-row">
        <span class="summary-chip summary-chip-total">Expected <strong>{{ (int) $audit->expected_total }}</strong></span>
        <span class="summary-chip summary-chip-matched">Matched <strong>{{ (int) $audit->matched_total }}</strong></span>
        <span class="summary-chip summary-chip-missing">Missing <strong>{{ $audit->missingTotal() }}</strong></span>
    </div>

    <table class="report">
        <thead>
            <tr>
                <th>#</th>
                <th>Lot Code</th>
                <th>Product</th>
                <th>SKU</th>
                <th>Stone</th>
                <th>Supplier</th>
                <th>Invoice #</th>
                <th>Purchase Date</th>
                <th class="text-right">Cost Price</th>
                <th class="text-right">Qty</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $index => $item)
                @php
                    $purchase = $item->purchaseProduct?->line?->purchase;
                    $supplier = $purchase?->supplier;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $item->lot_code ?: '—' }}
                        @if ($item->isUntrackable())
                            <span class="badge">no lot code</span>
                        @endif
                    </td>
                    <td>{{ $item->product?->title ?? '—' }}</td>
                    <td>{{ $item->product?->sku ?? '—' }}</td>
                    <td>{{ $item->product?->category?->name ?? '—' }}</td>
                    <td>{{ $supplier?->company_name ?: ($supplier?->name ?? '—') }}</td>
                    <td>{{ $purchase?->invoice_number ?? '—' }}</td>
                    <td>{{ $purchase?->purchase_date?->format('d M Y') ?? '—' }}</td>
                    <td class="text-right">{{ $settings->formatMoney((float) ($item->purchaseProduct?->price ?? 0)) }}</td>
                    <td class="text-right">1</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="empty-row">No missing stock — everything expected was scanned.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Generated by Paces &middot; {{ $generatedAt->format('d M Y H:i') }}</div>
</body>
</html>
