<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice &mdash; {{ $purchase->invoice_number }}</title>
    <link rel="shortcut icon" href="{{ $settings->faviconUrl() ?? asset('assets/images/favicon.ico') }}" />
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            margin: 0;
            padding: 24px 16px 48px;
            background: #f1f5f9;
            color: #1e293b;
        }
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            max-width: 820px;
            margin: 0 auto 16px;
            padding: 12px 16px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }
        .toolbar-title { display: flex; align-items: center; gap: 10px; }
        .toolbar-title a { color: #64748b; text-decoration: none; font-size: 20px; line-height: 1; }
        .toolbar-title h1 { font-size: 15px; margin: 0; font-weight: 700; }
        .toolbar-actions { display: flex; align-items: center; gap: 8px; }
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            border: 1px solid #e2e8f0; background: #fff; color: #334155;
            padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600;
            text-decoration: none; cursor: pointer;
        }
        .btn-primary { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }

        .sheet {
            max-width: 820px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 40px;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 24px;
            border-bottom: 2px solid #1e293b;
            margin-bottom: 24px;
        }
        .company-block img { max-height: 44px; margin-bottom: 8px; }
        .company-name { font-size: 18px; font-weight: 800; margin: 0 0 2px; }
        .company-meta { font-size: 12px; color: #64748b; line-height: 1.6; }

        .invoice-meta { text-align: right; }
        .invoice-meta h2 { margin: 0 0 6px; font-size: 22px; letter-spacing: 0.04em; color: #1d4ed8; }
        .invoice-meta .invoice-number { font-size: 14px; font-weight: 700; margin-bottom: 6px; }
        .invoice-meta .invoice-date { font-size: 12px; color: #64748b; }

        .badge {
            display: inline-block; padding: 2px 10px; border-radius: 999px;
            font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;
            margin-left: 4px;
        }
        .badge-draft     { background: #f1f5f9; color: #475569; }
        .badge-posted    { background: #ecfdf5; color: #059669; }
        .badge-cancelled { background: #fef2f2; color: #dc2626; }
        .badge-unpaid    { background: #fef2f2; color: #dc2626; }
        .badge-partial   { background: #fffbeb; color: #d97706; }
        .badge-paid      { background: #ecfdf5; color: #059669; }

        .party-row { display: flex; gap: 32px; margin-bottom: 28px; }
        .party-col { flex: 1; }
        .party-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; font-weight: 700; margin-bottom: 6px; }
        .party-name { font-size: 14px; font-weight: 700; margin-bottom: 2px; }
        .party-meta { font-size: 12px; color: #64748b; line-height: 1.7; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.items th {
            background: #f8fafc; text-align: left; padding: 8px 10px; font-size: 10px;
            text-transform: uppercase; letter-spacing: 0.03em; color: #64748b;
            border-bottom: 1px solid #e2e8f0;
        }
        table.items td { padding: 9px 10px; font-size: 12.5px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        table.items tr:last-child td { border-bottom: none; }
        .text-right { text-align: right; }
        .text-muted { color: #94a3b8; }

        .totals-wrap { display: flex; justify-content: flex-end; margin-bottom: 28px; }
        .totals { width: 280px; font-size: 13px; }
        .totals-row { display: flex; justify-content: space-between; padding: 5px 0; color: #475569; }
        .totals-row.grand { border-top: 2px solid #1e293b; margin-top: 6px; padding-top: 10px; font-size: 15px; font-weight: 800; color: #1e293b; }
        .totals-row.due { color: #dc2626; font-weight: 700; }
        .totals-row.paid { color: #059669; }

        .payments-title, .note-title { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #94a3b8; font-weight: 700; margin-bottom: 8px; }
        table.payments { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.payments th { text-align: left; padding: 6px 10px; font-size: 10px; text-transform: uppercase; color: #94a3b8; border-bottom: 1px solid #e2e8f0; }
        table.payments td { padding: 6px 10px; font-size: 12px; border-bottom: 1px solid #f1f5f9; }

        .note-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; font-size: 12.5px; color: #475569; margin-bottom: 24px; }

        .invoice-footer { text-align: center; font-size: 11px; color: #94a3b8; padding-top: 16px; border-top: 1px solid #f1f5f9; }

        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .sheet { border: none; border-radius: 0; max-width: 100%; padding: 0; }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <div class="toolbar-title">
            <a href="{{ route('purchases.show', $purchase) }}" title="Back to purchase">&larr;</a>
            <h1>Invoice {{ $purchase->invoice_number }}</h1>
        </div>
        <div class="toolbar-actions">
            <a href="{{ route('purchases.invoice.pdf', $purchase) }}" class="btn">
                Download PDF
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                Print
            </button>
        </div>
    </div>

    <div class="sheet">

        <div class="invoice-header">
            <div class="company-block">
                @if ($settings->logoUrl())
                    <img src="{{ $settings->logoUrl() }}" alt="{{ $settings->get('site_name', 'Sukaina Gems') }}">
                @endif
                <div class="company-name">{{ $settings->get('site_name', 'Sukaina Gems') }}</div>
                <div class="company-meta">
                    @if ($settings->get('contact_phone'))<div>{{ $settings->get('contact_phone') }}</div>@endif
                    @if ($settings->get('contact_email'))<div>{{ $settings->get('contact_email') }}</div>@endif
                </div>
            </div>
            <div class="invoice-meta">
                <h2>PURCHASE INVOICE</h2>
                <div class="invoice-number">{{ $purchase->invoice_number }}</div>
                <div class="invoice-date">{{ optional($purchase->purchase_date)->format('d M Y') }}</div>
                <div style="margin-top: 8px;">
                    <span class="badge badge-{{ $purchase->status }}">{{ $purchase->statusLabel() }}</span>
                    <span class="badge badge-{{ $purchase->payment_status }}">{{ $purchase->paymentStatusLabel() }}</span>
                </div>
            </div>
        </div>

        <div class="party-row">
            <div class="party-col">
                <div class="party-label">Supplier</div>
                <div class="party-name">{{ $purchase->supplier?->company_name ?: $purchase->supplier?->name }}</div>
                <div class="party-meta">
                    @if ($purchase->supplier?->company_name && $purchase->supplier?->name)
                        <div>{{ $purchase->supplier->name }}</div>
                    @endif
                    @if ($purchase->supplier?->phone)<div>{{ $purchase->supplier->phone }}</div>@endif
                    @if ($purchase->supplier?->email)<div>{{ $purchase->supplier->email }}</div>@endif
                    @if ($purchase->supplier?->gst_number)<div>GSTIN: {{ $purchase->supplier->gst_number }}</div>@endif
                    @if ($purchase->supplier?->address)<div>{{ $purchase->supplier->address }}</div>@endif
                    @if ($purchase->supplier?->city || $purchase->supplier?->state)
                        <div>{{ collect([$purchase->supplier?->city, $purchase->supplier?->state, $purchase->supplier?->zip_code])->filter()->implode(', ') }}</div>
                    @endif
                </div>
            </div>
            <div class="party-col">
                <div class="party-label">Delivered To</div>
                <div class="party-name">{{ $purchase->location?->name ?? '—' }}</div>
                <div class="party-meta">
                    @if ($purchase->location?->location_code)<div>{{ $purchase->location->location_code }}</div>@endif
                </div>
            </div>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th>Item</th>
                    <th>Stone</th>
                    <th class="text-right">Carat</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Tax</th>
                    <th class="text-right">Discount</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @php $rowIndex = 0; @endphp
                @foreach ($purchase->lines as $line)
                    @foreach ($line->rows as $row)
                        @php
                            $rowIndex++;
                            $rowSubtotal  = (float) $row->price * (float) $row->qty;
                            $rowTaxAmt    = (float) $row->tax_amount;
                            $rowDiscAmt   = (float) $row->discount_amount;
                            $rowTotal     = $rowSubtotal + $rowTaxAmt - $rowDiscAmt;
                        @endphp
                        <tr>
                            <td>{{ $rowIndex }}</td>
                            <td>{{ $row->product?->title ?? $line->title ?? '—' }}</td>
                            <td>{{ $line->category?->name ?? '—' }}</td>
                            <td class="text-right">{{ $row->carat_weight !== null ? rtrim(rtrim(number_format((float) $row->carat_weight, 3), '0'), '.') : '—' }}</td>
                            <td class="text-right">{{ (int) $row->qty }}</td>
                            <td class="text-right">{{ $settings->formatMoney((float) $row->price) }}</td>
                            <td class="text-right">
                                {{ $rowTaxAmt > 0 ? $settings->formatMoney($rowTaxAmt) : '—' }}
                                @if ($row->tax_percent > 0)<span class="text-muted">({{ rtrim(rtrim(number_format((float) $row->tax_percent, 2), '0'), '.') }}%)</span>@endif
                            </td>
                            <td class="text-right">{{ $rowDiscAmt > 0 ? $settings->formatMoney($rowDiscAmt) : '—' }}</td>
                            <td class="text-right">{{ $settings->formatMoney($rowTotal) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        <div class="totals-wrap">
            <div class="totals">
                <div class="totals-row">
                    <span>Subtotal</span>
                    <span>{{ $settings->formatMoney((float) $purchase->subtotal) }}</span>
                </div>
                @if ($purchase->tax_type === 'cgst_sgst')
                    <div class="totals-row">
                        <span>CGST</span>
                        <span>{{ $settings->formatMoney($purchase->tax_breakdown['cgst']) }}</span>
                    </div>
                    <div class="totals-row">
                        <span>SGST</span>
                        <span>{{ $settings->formatMoney($purchase->tax_breakdown['sgst']) }}</span>
                    </div>
                @elseif ($purchase->tax_type === 'igst')
                    <div class="totals-row">
                        <span>IGST</span>
                        <span>{{ $settings->formatMoney($purchase->tax_breakdown['igst']) }}</span>
                    </div>
                @endif
                @if ((float) $purchase->discount_total > 0)
                    <div class="totals-row">
                        <span>Discount</span>
                        <span>&minus;{{ $settings->formatMoney((float) $purchase->discount_total) }}</span>
                    </div>
                @endif
                <div class="totals-row grand">
                    <span>Grand Total</span>
                    <span>{{ $settings->formatMoney((float) $purchase->grand_total) }}</span>
                </div>
                <div class="totals-row paid">
                    <span>Paid</span>
                    <span>{{ $settings->formatMoney((float) $purchase->paid_amount) }}</span>
                </div>
                <div class="totals-row due">
                    <span>Balance Due</span>
                    <span>{{ $settings->formatMoney((float) $purchase->due_amount) }}</span>
                </div>
            </div>
        </div>

        @if ($purchase->payments->isNotEmpty())
            <div class="payments-title">Payments Received</div>
            <table class="payments">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchase->payments as $payment)
                        <tr>
                            <td>{{ optional($payment->payment_date)->format('d M Y') }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                            <td>{{ $payment->reference_number ?: '—' }}</td>
                            <td class="text-right">{{ $settings->formatMoney((float) $payment->amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($purchase->note)
            <div class="note-title">Note</div>
            <div class="note-box">{{ $purchase->note }}</div>
        @endif

        <div class="invoice-footer">
            Generated by {{ $settings->get('site_name', 'Sukaina Gems') }} &middot; {{ now()->format('d M Y H:i') }}
        </div>
    </div>

</body>
</html>
