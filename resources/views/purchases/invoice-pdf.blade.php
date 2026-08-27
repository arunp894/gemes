<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice — {{ $purchase->invoice_number }}</title>
    <style>
        {{--
            dompdf only renders a subset of CSS (no flexbox/grid, spotty
            custom-property support) so this stays table/inline-block
            layout with literal hex values — mirrors purchases/invoice.blade.php
            (the on-screen/print version) but restructured for dompdf.
        --}}
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10.5px; color: #1e293b; }
        .muted { color: #64748b; }

        table.header-table { width: 100%; border-bottom: 2px solid #1e293b; padding-bottom: 10px; margin-bottom: 16px; }
        table.header-table td { vertical-align: top; padding: 0; }
        .text-right { text-align: right; }

        .company-name { font-size: 15px; font-weight: bold; margin: 0 0 4px; }
        .invoice-title { font-size: 19px; font-weight: bold; letter-spacing: 1px; color: #1d4ed8; margin: 0 0 4px; }
        .invoice-number { font-size: 12px; font-weight: bold; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 8.5px; font-weight: bold; margin-left: 3px; }
        .badge-draft     { background: #f1f5f9; color: #475569; }
        .badge-posted    { background: #ecfdf5; color: #059669; }
        .badge-cancelled { background: #fef2f2; color: #dc2626; }
        .badge-unpaid    { background: #fef2f2; color: #dc2626; }
        .badge-partial   { background: #fffbeb; color: #d97706; }
        .badge-paid      { background: #ecfdf5; color: #059669; }

        table.party-table { width: 100%; margin-bottom: 16px; }
        table.party-table td { vertical-align: top; width: 50%; padding: 0; }
        .party-label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; font-weight: bold; margin-bottom: 3px; }
        .party-name { font-size: 12px; font-weight: bold; margin-bottom: 2px; }
        .party-meta { font-size: 9.5px; color: #64748b; line-height: 1.5; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.items th {
            background: #f8fafc; text-align: left; padding: 6px; font-size: 8.5px;
            text-transform: uppercase; color: #64748b; border-bottom: 1px solid #e2e8f0;
        }
        table.items td { padding: 5px 6px; font-size: 9.5px; border-bottom: 1px solid #eceef1; }

        table.totals { width: 260px; float: right; font-size: 10px; margin-bottom: 16px; }
        table.totals td { padding: 3px 0; }
        table.totals tr.grand td { border-top: 2px solid #1e293b; padding-top: 6px; font-size: 12px; font-weight: bold; }
        table.totals tr.due td { color: #dc2626; font-weight: bold; }
        table.totals tr.paid td { color: #059669; }

        .clear { clear: both; }

        .payments-title { font-size: 9px; text-transform: uppercase; color: #94a3b8; font-weight: bold; margin: 10px 0 6px; }
        table.payments { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.payments th { text-align: left; padding: 4px 6px; font-size: 8.5px; color: #94a3b8; border-bottom: 1px solid #e2e8f0; }
        table.payments td { padding: 4px 6px; font-size: 9.5px; border-bottom: 1px solid #eceef1; }

        .note-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 10px; font-size: 9.5px; color: #475569; margin-bottom: 14px; }
        .footer { margin-top: 10px; padding-top: 8px; border-top: 1px solid #eceef1; font-size: 8px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td>
                <div class="company-name">{{ $settings->get('site_name', 'Sukaina Gems') }}</div>
                <div class="muted">
                    @if ($settings->get('contact_phone')) {{ $settings->get('contact_phone') }} @endif
                    @if ($settings->get('contact_email')) &middot; {{ $settings->get('contact_email') }} @endif
                </div>
            </td>
            <td class="text-right">
                <div class="invoice-title">PURCHASE INVOICE</div>
                <div class="invoice-number">{{ $purchase->invoice_number }}</div>
                <div class="muted">{{ optional($purchase->purchase_date)->format('d M Y') }}</div>
                <div>
                    <span class="badge badge-{{ $purchase->status }}">{{ $purchase->statusLabel() }}</span>
                    <span class="badge badge-{{ $purchase->payment_status }}">{{ $purchase->paymentStatusLabel() }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="party-table">
        <tr>
            <td>
                <div class="party-label">Supplier</div>
                <div class="party-name">{{ $purchase->supplier?->company_name ?: $purchase->supplier?->name }}</div>
                <div class="party-meta">
                    @if ($purchase->supplier?->company_name && $purchase->supplier?->name)
                        {{ $purchase->supplier->name }}<br>
                    @endif
                    @if ($purchase->supplier?->phone) {{ $purchase->supplier->phone }}<br> @endif
                    @if ($purchase->supplier?->email) {{ $purchase->supplier->email }}<br> @endif
                    @if ($purchase->supplier?->gst_number) GSTIN: {{ $purchase->supplier->gst_number }}<br> @endif
                    @if ($purchase->supplier?->address) {{ $purchase->supplier->address }}<br> @endif
                    @if ($purchase->supplier?->city || $purchase->supplier?->state)
                        {{ collect([$purchase->supplier?->city, $purchase->supplier?->state, $purchase->supplier?->zip_code])->filter()->implode(', ') }}
                    @endif
                </div>
            </td>
            <td>
                <div class="party-label">Delivered To</div>
                <div class="party-name">{{ $purchase->location?->name ?? '—' }}</div>
                <div class="party-meta">
                    @if ($purchase->location?->location_code) {{ $purchase->location->location_code }} @endif
                </div>
            </td>
        </tr>
    </table>

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
                        $rowSubtotal = (float) $row->price * (float) $row->qty;
                        $rowTaxAmt   = (float) $row->tax_amount;
                        $rowDiscAmt  = (float) $row->discount_amount;
                        $rowTotal    = $rowSubtotal + $rowTaxAmt - $rowDiscAmt;
                    @endphp
                    <tr>
                        <td>{{ $rowIndex }}</td>
                        <td>{{ $row->product?->title ?? $line->title ?? '—' }}</td>
                        <td>{{ $line->category?->name ?? '—' }}</td>
                        <td class="text-right">{{ $row->carat_weight !== null ? rtrim(rtrim(number_format((float) $row->carat_weight, 3), '0'), '.') : '—' }}</td>
                        <td class="text-right">{{ (int) $row->qty }}</td>
                        <td class="text-right">{{ $settings->formatMoney((float) $row->price) }}</td>
                        <td class="text-right">{{ $rowTaxAmt > 0 ? $settings->formatMoney($rowTaxAmt) : '—' }}</td>
                        <td class="text-right">{{ $rowDiscAmt > 0 ? $settings->formatMoney($rowDiscAmt) : '—' }}</td>
                        <td class="text-right">{{ $settings->formatMoney($rowTotal) }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td class="text-right">{{ $settings->formatMoney((float) $purchase->subtotal) }}</td>
        </tr>
        @if ($purchase->tax_type === 'cgst_sgst')
            <tr>
                <td>CGST</td>
                <td class="text-right">{{ $settings->formatMoney($purchase->tax_breakdown['cgst']) }}</td>
            </tr>
            <tr>
                <td>SGST</td>
                <td class="text-right">{{ $settings->formatMoney($purchase->tax_breakdown['sgst']) }}</td>
            </tr>
        @elseif ($purchase->tax_type === 'igst')
            <tr>
                <td>IGST</td>
                <td class="text-right">{{ $settings->formatMoney($purchase->tax_breakdown['igst']) }}</td>
            </tr>
        @endif
        @if ((float) $purchase->discount_total > 0)
            <tr>
                <td>Discount</td>
                <td class="text-right">&minus;{{ $settings->formatMoney((float) $purchase->discount_total) }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td>Grand Total</td>
            <td class="text-right">{{ $settings->formatMoney((float) $purchase->grand_total) }}</td>
        </tr>
        <tr class="paid">
            <td>Paid</td>
            <td class="text-right">{{ $settings->formatMoney((float) $purchase->paid_amount) }}</td>
        </tr>
        <tr class="due">
            <td>Balance Due</td>
            <td class="text-right">{{ $settings->formatMoney((float) $purchase->due_amount) }}</td>
        </tr>
    </table>
    <div class="clear"></div>

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
        <div class="note-box">{{ $purchase->note }}</div>
    @endif

    <div class="footer">Generated by {{ $settings->get('site_name', 'Sukaina Gems') }} &middot; {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
