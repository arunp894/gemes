<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $rental->rent_number }} - Memo Agreement</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #111; margin: 24px; }
        h1 { font-size: 20px; margin: 0; } h2 { font-size: 14px; margin: 18px 0 6px; text-transform: uppercase; letter-spacing: .05em; }
        .top { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #111; padding-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #999; padding: 5px 7px; text-align: left; }
        th { background: #f1f1f1; } .r { text-align: right; } .meta td { border: 0; padding: 2px 6px 2px 0; }
        .sign { display: flex; justify-content: space-between; margin-top: 60px; } .sign div { width: 40%; border-top: 1px solid #111; padding-top: 4px; text-align: center; }
        .terms { font-size: 11px; color: #333; } .btn { margin-bottom: 12px; }
        @media print { .btn { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <button class="btn" onclick="window.print()">Print</button>

    <div class="top">
        <div><h1>{{ $settings->get('site_name', 'Sukaina Gems') }}</h1><div>404/A Fourth Floor</div><div>JTC</div></div>
        <div style="text-align:right"><strong>{{ $rental->rent_number }}</strong><br>{{ $rental->created_at->format('d M Y') }}</div>
    </div>

    <h2>Customer</h2>
    <table class="meta">
        <tr><td><strong>Customer</strong></td><td>{{ $rental->customer?->display_name }}</td><td><strong>Phone</strong></td><td>{{ $rental->customer?->phone }}</td></tr>
        <tr><td><strong>From</strong></td><td>{{ $rental->start_at->format('d M Y H:i') }}</td><td><strong>To (due back)</strong></td><td>{{ $rental->due_at->format('d M Y H:i') }}</td></tr>
        <tr><td><strong>Location</strong></td><td>{{ $rental->location?->name }}</td><td></td><td></td></tr>
    </table>

    <h2>Stones</h2>
    <table>
        <thead><tr><th>Lot</th><th>Stone</th><th class="r">Pcs</th><th class="r">CT</th><th class="r">Rate / CT</th><th class="r">Memo Total</th><th class="r">Deposit</th></tr></thead>
        <tbody>
            @foreach ($rental->lines as $l)
                <tr>
                    <td>{{ $l->purchaseProduct?->lot_code }}</td><td>{{ $l->product?->title }}</td>
                    <td class="r">{{ $l->qty }}</td>
                    <td class="r">{{ $l->carat_weight !== null ? rtrim(rtrim(number_format((float) $l->carat_weight, 3), '0'), '.') : '-' }}</td>
                    <td class="r">{{ (float) $l->rent_rate > 0 ? $settings->formatMoney($l->rent_rate) : '-' }}</td>
                    <td class="r">{{ $settings->formatMoney($l->rent_amount) }}</td><td class="r">{{ $settings->formatMoney($l->deposit_amount) }}</td>
                </tr>
            @endforeach
            <tr><th colspan="5" class="r">Total</th><th class="r">{{ $settings->formatMoney($rental->rent_total) }}</th><th class="r">{{ $settings->formatMoney($rental->deposit_total) }}</th></tr>
        </tbody>
    </table>

    @if ($rental->note)<p><strong>Note:</strong> {{ $rental->note }}</p>@endif

    <p class="terms">The customer received the stones listed above in good condition and agrees to return them by the due date. The deposit is refunded
        on return of all stones, less any charge for late return or damage. Stones not returned are charged against the deposit.</p>

    <div class="sign"><div>Customer signature</div><div>Authorised signature</div></div>
</body>
</html>
