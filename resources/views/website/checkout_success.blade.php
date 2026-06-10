@extends('website.layout')

@section('title', 'Order Confirmed — Sukaina Gems')

@section('content')

<div style="min-height:70vh;display:flex;align-items:center;justify-content:center;background:var(--dark-900);padding:60px 20px">
  <div style="max-width:600px;width:100%;text-align:center">

    {{-- Animated checkmark --}}
    <div style="width:90px;height:90px;border-radius:50%;background:rgba(0,191,176,.1);border:2px solid rgba(0,191,176,.3);display:flex;align-items:center;justify-content:center;margin:0 auto 28px;box-shadow:0 0 40px rgba(0,191,176,.25)">
      <div style="font-size:40px">✓</div>
    </div>

    <div class="sg-eyebrow" style="text-align:center;margin-bottom:10px">Payment Successful</div>
    <h1 style="font-family:'Cormorant Garamond',serif;font-size:52px;font-weight:700;margin-bottom:14px">
      Thank <em style="color:var(--teal-300);font-style:italic">You</em>!
    </h1>
    <p style="font-size:16px;color:var(--white-dim);margin-bottom:10px;line-height:1.7">
      Your order has been placed successfully. We'll be in touch shortly with shipping details.
    </p>

    @if($sale)
      <p style="font-size:13px;color:var(--white-faint);margin-bottom:8px">
        Order Reference: <code style="color:var(--teal-300);background:rgba(0,191,176,.07);padding:3px 8px;border-radius:3px">{{ $sale->sale_number }}</code>
      </p>
    @endif
    <p style="font-size:12px;color:var(--white-faint);margin-bottom:32px">
      PayPal ID: <code style="color:var(--teal-300);background:rgba(0,191,176,.07);padding:3px 8px;border-radius:3px">{{ $orderId }}</code>
    </p>

    {{-- Mini order summary if sale available --}}
    @if($sale && $sale->lines->count())
      <div style="background:var(--dark-800);border:1px solid rgba(0,191,176,.1);border-radius:4px;padding:20px;margin-bottom:28px;text-align:left">
        <div style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid rgba(0,191,176,.08)">Items Ordered</div>
        @foreach($sale->lines as $line)
          <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(0,191,176,.05);font-size:13px">
            <span style="color:var(--white-dim)">{{ $line->product?->title ?? 'Item' }}</span>
            <span style="color:var(--teal-300);font-family:'Cormorant Garamond',serif;font-size:15px;font-weight:700">{{ $settings->formatPrice($line->total) }}</span>
          </div>
        @endforeach
        <div style="display:flex;justify-content:space-between;padding-top:12px;font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:700">
          <span style="font-size:14px;color:var(--white);font-family:'Jost',sans-serif">Total Paid</span>
          <span style="color:var(--teal-300)">{{ $settings->formatPrice($sale->grand_total) }}</span>
        </div>
      </div>
    @endif

    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-bottom:28px">
      @if(auth('customer')->check())
        <a href="{{ route('website.account.orders') }}" class="sg-btn-primary">View My Orders →</a>
      @endif
      <a href="{{ route('website.collections') }}" class="sg-btn-outline">Continue Shopping</a>
    </div>

    <div style="padding:20px;background:var(--dark-800);border:1px solid rgba(0,191,176,.1);border-radius:4px">
      <div style="font-size:13px;color:var(--white-faint);line-height:1.9">
        📦 Your gem will be carefully packaged and shipped with full insurance.<br>
        📄 Your PayPal receipt has been emailed to your PayPal address.<br>
        @if($settings->get('contact_whatsapp'))
          💬 Questions? <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings->get('contact_whatsapp')) }}" target="_blank" style="color:var(--teal-300);text-decoration:none">Contact us on WhatsApp</a>
        @endif
      </div>
    </div>

  </div>
</div>

@endsection
