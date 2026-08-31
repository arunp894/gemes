@extends('emails.layout')

@section('title', 'Order Confirmed — ' . $sale->sale_number)

@section('content')
    <h1 style="margin:0 0 16px;font-family:Georgia,serif;font-size:24px;font-weight:600;color:#ffffff;">
        Thank you for your order, {{ $sale->customer->name }}!
    </h1>

    <p style="margin:0 0 24px;">
        We've received your payment and your order is being prepared. Here's a summary for your records.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 20px;">
        <tr>
            <td style="font-size:12px;color:rgba(230,245,242,.55);padding-bottom:2px;">Order Number</td>
            <td style="font-size:12px;color:rgba(230,245,242,.55);padding-bottom:2px;" align="right">Order Date</td>
        </tr>
        <tr>
            <td style="font-size:15px;font-weight:600;color:#ffffff;">{{ $sale->sale_number }}</td>
            <td style="font-size:15px;font-weight:600;color:#ffffff;" align="right">{{ $sale->sale_date->format('d M Y') }}</td>
        </tr>
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0 0 20px;">
        <tr>
            <td style="padding:8px 0;border-bottom:1px solid rgba(0,191,176,.15);font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:rgba(230,245,242,.5);">Item</td>
            <td style="padding:8px 0;border-bottom:1px solid rgba(0,191,176,.15);font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:rgba(230,245,242,.5);" align="center">Qty</td>
            <td style="padding:8px 0;border-bottom:1px solid rgba(0,191,176,.15);font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:rgba(230,245,242,.5);" align="right">Amount</td>
        </tr>
        @foreach ($sale->lines as $line)
        <tr>
            <td style="padding:12px 0;border-bottom:1px solid rgba(255,255,255,.06);font-size:13px;color:#e6f5f2;">
                {{ $line->product?->title ?? 'Item' }}
                @if ($line->carat_weight)
                    <br><span style="font-size:11px;color:rgba(230,245,242,.5);">{{ rtrim(rtrim(number_format((float) $line->carat_weight, 3), '0'), '.') }} ct</span>
                @endif
            </td>
            <td style="padding:12px 0;border-bottom:1px solid rgba(255,255,255,.06);font-size:13px;color:#e6f5f2;" align="center">{{ $line->qty }}</td>
            <td style="padding:12px 0;border-bottom:1px solid rgba(255,255,255,.06);font-size:13px;color:#e6f5f2;" align="right">{{ $settings->formatMoney($line->total) }}</td>
        </tr>
        @endforeach
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 28px;">
        <tr>
            <td style="font-size:15px;font-weight:700;color:#ffffff;">Total</td>
            <td style="font-size:15px;font-weight:700;color:#2dd4bf;" align="right">{{ $settings->formatMoney($sale->grand_total) }}</td>
        </tr>
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <td style="border-radius:4px;background:linear-gradient(135deg,#2dd4bf,#0f766e);">
                <a href="{{ route('website.account.order-detail', $sale) }}"
                   style="display:inline-block;padding:12px 28px;font-size:13px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;color:#04211d;text-decoration:none;">
                    View Order &rarr;
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:28px 0 0;font-size:12px;color:rgba(230,245,242,.5);">
        Questions about your order? Just reply to this email and we'll help right away.
    </p>
@endsection
