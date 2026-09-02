@php
    // Prefer the shipping address staff set on the sale itself (see
    // SaleController::updateShippingDetails()) — falls back to the
    // customer's own profile address, since that's the best guess at
    // delivery destination immediately after checkout, before staff have
    // had a chance to confirm/override it.
    $shipLine1  = $sale->shipping_address_line1  ?? $sale->customer->address_line1  ?? null;
    $shipLine2  = $sale->shipping_address_line2  ?? $sale->customer->address_line2  ?? null;
    $shipCity   = $sale->shipping_city           ?? $sale->customer->city           ?? null;
    $shipState  = $sale->shipping_state          ?? $sale->customer->state          ?? null;
    $shipZip    = $sale->shipping_zip_code       ?? $sale->customer->zip_code       ?? null;
    $shipCountry = $sale->shipping_country       ?? $sale->customer->country        ?? null;
    $hasAddress = $shipLine1 || $shipCity || $shipCountry;
@endphp
@extends('emails.layout')

@section('title', 'Order Confirmation – ' . $settings->get('site_name', 'Sukaina Gems') . ' | #' . $sale->sale_number)

@section('content')
    <h1 style="margin:0 0 16px;font-family:Georgia,serif;font-size:24px;font-weight:600;color:#ffffff;">
        Thank you for your order, {{ $sale->customer->name }}!
    </h1>

    <p style="margin:0 0 24px;">
        Thank you for your order with {{ $settings->get('site_name', 'Sukaina Gems') }}. Your order has been successfully received.
    </p>

    <div style="margin:0 0 24px;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:rgba(230,245,242,.5);">Order Details</div>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 20px;">
        <tr>
            <td style="font-size:12px;color:rgba(230,245,242,.55);padding-bottom:2px;">Order No.</td>
            <td style="font-size:12px;color:rgba(230,245,242,.55);padding-bottom:2px;" align="center">Order Date</td>
            <td style="font-size:12px;color:rgba(230,245,242,.55);padding-bottom:2px;" align="right">Status</td>
        </tr>
        <tr>
            <td style="font-size:15px;font-weight:600;color:#ffffff;">#{{ $sale->sale_number }}</td>
            <td style="font-size:15px;font-weight:600;color:#ffffff;" align="center">{{ $sale->sale_date->format('d M Y') }}</td>
            <td style="font-size:15px;font-weight:600;color:#2dd4bf;" align="right">Confirmed</td>
        </tr>
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0 0 20px;">
        <tr>
            <td style="padding:8px 0;border-bottom:1px solid rgba(0,191,176,.15);font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:rgba(230,245,242,.5);">Product</td>
            <td style="padding:8px 0;border-bottom:1px solid rgba(0,191,176,.15);font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:rgba(230,245,242,.5);" align="center">Carat</td>
            <td style="padding:8px 0;border-bottom:1px solid rgba(0,191,176,.15);font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:rgba(230,245,242,.5);" align="center">Qty</td>
            <td style="padding:8px 0;border-bottom:1px solid rgba(0,191,176,.15);font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:rgba(230,245,242,.5);" align="right">Price</td>
        </tr>
        @foreach ($sale->lines as $line)
        <tr>
            <td style="padding:12px 0;border-bottom:1px solid rgba(255,255,255,.06);font-size:13px;color:#e6f5f2;">
                {{ $line->product?->title ?? 'Item' }}
            </td>
            <td style="padding:12px 0;border-bottom:1px solid rgba(255,255,255,.06);font-size:13px;color:#e6f5f2;" align="center">
                {{ $line->carat_weight ? rtrim(rtrim(number_format((float) $line->carat_weight, 3), '0'), '.') . ' ct' : '—' }}
            </td>
            <td style="padding:12px 0;border-bottom:1px solid rgba(255,255,255,.06);font-size:13px;color:#e6f5f2;" align="center">{{ $line->qty }}</td>
            <td style="padding:12px 0;border-bottom:1px solid rgba(255,255,255,.06);font-size:13px;color:#e6f5f2;" align="right">{{ $settings->formatMoney($line->total) }}</td>
        </tr>
        @endforeach
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 28px;">
        <tr>
            <td style="padding:2px 0;font-size:13px;color:rgba(230,245,242,.7);">Subtotal</td>
            <td style="padding:2px 0;font-size:13px;color:rgba(230,245,242,.7);" align="right">{{ $settings->formatMoney($sale->subtotal) }}</td>
        </tr>
        <tr>
            <td style="padding:2px 0 12px;font-size:13px;color:rgba(230,245,242,.7);">Shipping</td>
            <td style="padding:2px 0 12px;font-size:13px;color:rgba(230,245,242,.7);" align="right">{{ $settings->formatMoney($sale->shipping_charge) }}</td>
        </tr>
        <tr>
            <td style="padding-top:12px;border-top:1px solid rgba(0,191,176,.15);font-size:15px;font-weight:700;color:#ffffff;">Total</td>
            <td style="padding-top:12px;border-top:1px solid rgba(0,191,176,.15);font-size:15px;font-weight:700;color:#2dd4bf;" align="right">{{ $settings->formatMoney($sale->grand_total) }}</td>
        </tr>
    </table>

    @if ($sale->needsShipping())
        <div style="margin:0 0 8px;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:rgba(230,245,242,.5);">Delivery Details</div>
        <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 24px;font-size:13px;color:#e6f5f2;line-height:1.6;">
            <tr><td style="padding:2px 0;color:rgba(230,245,242,.55);">Shipping To</td></tr>
            <tr><td style="padding:0 0 8px;">{{ $sale->customer->name }}</td></tr>
            @if ($hasAddress)
                <tr><td style="padding:2px 0;color:rgba(230,245,242,.55);">Address</td></tr>
                <tr><td style="padding:0 0 8px;">
                    {{ $shipLine1 }}@if ($shipLine2)<br>{{ $shipLine2 }}@endif
                    @if ($shipCity || $shipState || $shipZip)<br>{{ trim(implode(', ', array_filter([$shipCity, $shipState, $shipZip]))) }}@endif
                    @if ($shipCountry)<br>{{ $shipCountry }}@endif
                </td></tr>
            @endif
            @if ($sale->shipping_carrier)
                <tr><td style="padding:2px 0;color:rgba(230,245,242,.55);">Shipping Method</td></tr>
                <tr><td style="padding:0;">{{ $sale->shipping_carrier }}</td></tr>
            @endif
        </table>

        <p style="margin:0 0 24px;font-size:12px;color:rgba(230,245,242,.5);">
            @if ($sale->tracking_number)
                Tracking number: <span style="color:#e6f5f2;">{{ $sale->tracking_number }}</span>
            @else
                Your tracking details will be shared once your order has been dispatched.
            @endif
        </p>
    @endif

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

    <p style="margin:20px 0 0;font-size:12px;color:rgba(230,245,242,.4);">
        Thank you for choosing {{ $settings->get('site_name', 'Sukaina Gems') }}.
    </p>
@endsection
