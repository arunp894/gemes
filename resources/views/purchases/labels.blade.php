<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print Labels &mdash; {{ $purchase->invoice_number }}</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.12.3/dist/JsBarcode.all.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            margin: 0;
            padding: 16px;
            background: #f4f5f7;
            color: #1f2430;
        }
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            max-width: 900px;
            margin: 0 auto 16px;
            padding: 12px 16px;
            background: #fff;
            border: 1px solid #e2e4e9;
            border-radius: 8px;
        }
        .toolbar h1 { font-size: 15px; margin: 0; }
        .toolbar p { margin: 2px 0 0; font-size: 12px; color: #6b7280; }
        .toolbar-actions { display: flex; align-items: center; }
        .toolbar a { font-size: 12px; color: #6b7280; text-decoration: none; margin-right: 14px; }
        .toolbar button {
            border: none;
            background: #3b5bfd;
            color: #fff;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .toolbar button:hover { background: #2f49d1; }

        .sheet {
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            gap: 8mm;
        }
        .label {
            width: 2in;
            height: 1in;
            border: 1px dashed #b6bac4;
            border-radius: 4px;
            padding: 3mm 3mm 2mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            page-break-inside: avoid;
            background: #fff;
            overflow: hidden;
        }
        .label-top {
            width: 100%;
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 4px;
        }
        .label .title {
            flex: 1 1 auto;
            min-width: 0;
            font-size: 8px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
            text-align: left;
        }
        .label .price-code {
            flex: 0 0 auto;
            font-family: "Courier New", monospace;
            font-weight: 700;
            font-size: 9px;
            letter-spacing: 0.5px;
        }
        .label .meta {
            width: 100%;
            font-size: 7px;
            color: #4b5563;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 1mm;
            text-align: left;
        }
        .label svg { max-width: 100%; height: auto; display: block; }
        .label .barcode-error { font-size: 8px; color: #b91c1c; }

        .empty {
            max-width: 900px;
            margin: 60px auto;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        .empty a { color: #3b5bfd; }

        @media print {
            body { background: #fff; padding: 0; margin: 0; }
            .no-print { display: none !important; }
            .sheet { display: block; }
            .label {
                border: none;
                border-radius: 0;
                break-after: page;
                page-break-after: always;
            }
            .label:last-child {
                break-after: auto;
                page-break-after: auto;
            }
            @page {
                size: 2in 1in;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <div class="toolbar no-print">
        <div>
            <h1>Labels &mdash; {{ $purchase->invoice_number }}</h1>
            <p>{{ $labels->count() }} label{{ $labels->count() === 1 ? '' : 's' }} ready to print</p>
        </div>
        <div class="toolbar-actions">
            <a href="{{ route('purchases.show', $purchase) }}">&larr; Back to purchase</a>
            <button type="button" onclick="window.print()">Print</button>
        </div>
    </div>

    @if ($labels->isEmpty())
        <div class="empty">
            No items were selected. <a href="{{ route('purchases.show', $purchase) }}">Go back</a> and select at least one row.
        </div>
    @else
        <div class="sheet">
            @foreach ($labels as $row)
                @php
                    // New-style rows own their product directly; historical
                    // rows (pre this column existing) fall back to the
                    // line's shared product.
                    $product = $row->product ?? $row->line?->product;
                    $carat   = $row->carat_weight !== null
                        ? rtrim(rtrim(number_format((float) $row->carat_weight, 3), '0'), '.') . ' ct'
                        : null;
                @endphp
                <div class="label">
                    <div class="label-top">
                        <div class="title">{{ $product?->stone_type ?: ($product?->title ?? 'Unknown product') }}</div>
                        <div class="price-code">{{ $row->priceCode() }}</div>
                    </div>
                    <div class="meta">
                        SKU: {{ $product?->sku ?? '—' }}
                        @if ($carat)
                            &middot; {{ $carat }}
                        @endif
                    </div>
                    <svg class="barcode" data-value="{{ $row->lot_code }}"></svg>
                </div>
            @endforeach
        </div>
    @endif

    <script>
        document.querySelectorAll('svg.barcode').forEach(function (el) {
            try {
                JsBarcode(el, el.dataset.value || '', {
                    format: 'CODE128',
                    displayValue: true,
                    fontSize: 11,
                    height: 30,
                    margin: 0,
                });
            } catch (e) {
                var msg = document.createElement('div');
                msg.className = 'barcode-error';
                msg.textContent = 'Barcode error';
                el.replaceWith(msg);
            }
        });
    </script>

</body>
</html>
