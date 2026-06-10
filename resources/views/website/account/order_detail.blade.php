@extends('website.layout')

@section('title', 'Order ' . $sale->sale_number . ' — Sukaina Gems')

@section('content')
<div style="padding:48px 60px;background:var(--dark-900);min-height:80vh">
  <div style="max-width:900px;margin:0 auto">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:32px">
      <div>
        <a href="{{ route('website.account.orders') }}" style="font-size:12px;letter-spacing:1.5px;text-transform:uppercase;color:var(--teal-300);text-decoration:none">← Back to Orders</a>
        <h1 style="font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:600;color:var(--white);margin-top:8px">{{ $sale->sale_number }}</h1>
        <p style="font-size:13px;color:var(--white-faint);margin-top:4px">Placed on {{ $sale->sale_date->format('d F Y') }}</p>
      </div>
      <div style="text-align:right">
        @php
          $sc = match($sale->status) {
            'completed' => ['bg'=>'rgba(80,200,130,.12)','b'=>'rgba(80,200,130,.25)','c'=>'#7ec87e'],
            'posted'    => ['bg'=>'rgba(0,191,176,.12)',  'b'=>'rgba(0,191,176,.25)',  'c'=>'var(--teal-300)'],
            default     => ['bg'=>'rgba(200,160,80,.12)','b'=>'rgba(200,160,80,.25)','c'=>'var(--gold-light)'],
          };
        @endphp
        <span style="font-size:11px;letter-spacing:1.5px;text-transform:uppercase;padding:6px 14px;border-radius:2px;background:{{ $sc['bg'] }};border:1px solid {{ $sc['b'] }};color:{{ $sc['c'] }}">
          {{ $sale->statusLabel() }}
        </span>
      </div>
    </div>

    {{-- Line items --}}
    <div style="background:var(--dark-800);border:1px solid rgba(0,191,176,.1);border-radius:6px;margin-bottom:24px;overflow:hidden">
      <div style="padding:18px 22px;border-bottom:1px solid rgba(0,191,176,.08)">
        <div style="font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600">Items Ordered</div>
      </div>
      @foreach($sale->lines as $line)
        <div style="display:flex;align-items:center;gap:18px;padding:18px 22px;border-top:1px solid rgba(0,191,176,.06)">
          {{-- Product image --}}
          <div style="width:64px;height:64px;flex-shrink:0;background:var(--dark-750);border-radius:4px;overflow:hidden;display:flex;align-items:center;justify-content:center">
            @if($line->product && $line->product->primary_thumb_url)
              <img src="{{ $line->product->primary_thumb_url }}" alt="" style="width:100%;height:100%;object-fit:cover">
            @else
              <div style="width:32px;height:32px;clip-path:polygon(50% 0%,93% 25%,93% 75%,50% 100%,7% 75%,7% 25%);background:linear-gradient(135deg,var(--teal-300),var(--teal-700))"></div>
            @endif
          </div>
          {{-- Details --}}
          <div style="flex:1">
            <div style="font-weight:500;font-size:15px;color:var(--white)">{{ $line->product?->title ?? 'Product' }}</div>
            @if($line->barcode)
              <div style="font-size:11px;color:var(--white-faint);margin-top:3px">SKU: {{ $line->barcode }}</div>
            @endif
            @if($line->product?->carat_weight)
              <div style="font-size:12px;color:var(--teal-300);margin-top:3px">{{ $line->product->carat_weight }} ct</div>
            @endif
          </div>
          {{-- Qty & Price --}}
          <div style="text-align:right">
            <div style="font-size:13px;color:var(--white-faint);margin-bottom:4px">Qty: {{ $line->qty }}</div>
            <div style="font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:700;color:var(--teal-300)">{{ $settings->formatPrice($line->total) }}</div>
          </div>
        </div>
      @endforeach
    </div>

    {{-- Totals + info --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

      {{-- Order summary --}}
      <div style="background:var(--dark-800);border:1px solid rgba(0,191,176,.1);border-radius:6px;padding:22px">
        <div style="font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid rgba(0,191,176,.08)">Order Summary</div>
        <div style="font-size:13px;color:var(--white-dim)">
          @php $rows = [['Subtotal', $sale->subtotal],['Shipping','0'],['Tax',$sale->tax_total],['Grand Total',$sale->grand_total]]; @endphp
          @foreach($rows as [$label, $val])
            <div style="display:flex;justify-content:space-between;margin-bottom:10px;{{ $label === 'Grand Total' ? 'padding-top:12px;border-top:1px solid rgba(0,191,176,.1);margin-top:4px;font-weight:500;font-size:15px' : '' }}">
              <span style="{{ $label === 'Grand Total' ? 'color:var(--white)' : 'color:var(--white-faint)' }}">{{ $label }}</span>
              <span style="{{ $label === 'Grand Total' ? 'font-family:Cormorant Garamond,serif;font-size:20px;font-weight:700;color:var(--teal-300)' : '' }}">{{ $settings->formatPrice($val) }}</span>
            </div>
          @endforeach
        </div>
        <div style="margin-top:16px;padding-top:14px;border-top:1px solid rgba(0,191,176,.08);display:flex;align-items:center;justify-content:space-between">
          <span style="font-size:12px;color:var(--white-faint)">Payment Status</span>
          @php
            $pc = match($sale->payment_status) {
              'paid'    => ['c'=>'#7ec87e','b'=>'rgba(80,200,130,.25)','bg'=>'rgba(80,200,130,.12)'],
              'partial' => ['c'=>'var(--gold-light)','b'=>'rgba(200,160,80,.25)','bg'=>'rgba(200,160,80,.12)'],
              default   => ['c'=>'#e07070','b'=>'rgba(220,80,80,.25)','bg'=>'rgba(220,80,80,.12)'],
            };
          @endphp
          <span style="font-size:10px;letter-spacing:1.5px;text-transform:uppercase;padding:4px 10px;border-radius:2px;background:{{ $pc['bg'] }};border:1px solid {{ $pc['b'] }};color:{{ $pc['c'] }}">
            {{ $sale->paymentStatusLabel() }}
          </span>
        </div>
      </div>

      {{-- Delivery / notes --}}
      <div style="background:var(--dark-800);border:1px solid rgba(0,191,176,.1);border-radius:6px;padding:22px">
        <div style="font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid rgba(0,191,176,.08)">Details</div>
        <div style="font-size:13px;color:var(--white-dim)">
          <div style="margin-bottom:12px">
            <span style="color:var(--white-faint)">Order Date</span><br>
            <span style="margin-top:4px;display:block">{{ $sale->sale_date->format('d F Y') }}</span>
          </div>
          @if($sale->location)
            <div style="margin-bottom:12px">
              <span style="color:var(--white-faint)">Fulfilled by</span><br>
              <span style="margin-top:4px;display:block">{{ $sale->location->name }}</span>
            </div>
          @endif
          @if($sale->note)
            <div style="margin-bottom:12px">
              <span style="color:var(--white-faint)">Notes</span><br>
              <span style="margin-top:4px;display:block;font-size:12px">{{ $sale->note }}</span>
            </div>
          @endif
        </div>
        <div style="margin-top:auto;padding-top:20px">
          <p style="font-size:12px;color:var(--white-faint);margin-bottom:14px;line-height:1.7">Need help with this order? Contact us on WhatsApp or email.</p>
          @if($settings->get('contact_whatsapp'))
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings->get('contact_whatsapp')) }}?text=Hi, I have a question about order {{ $sale->sale_number }}"
               target="_blank" class="sg-btn-outline" style="font-size:11px">💬 WhatsApp Support</a>
          @endif
        </div>
      </div>

    </div>

  </div>
</div>
@endsection
