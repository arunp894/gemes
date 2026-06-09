@extends('website.layout')

@section('title', 'Order Confirmed')

@section('content')

<div style="min-height:70vh;display:flex;align-items:center;justify-content:center;background:var(--dark-900);padding:60px">
  <div style="max-width:520px;text-align:center">

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
    <p style="font-size:13px;color:var(--white-faint);margin-bottom:32px">
      PayPal Order ID: <code style="color:var(--teal-300);background:rgba(0,191,176,.07);padding:3px 8px;border-radius:3px">{{ $orderId }}</code>
    </p>

    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
      <a href="{{ route('website.collections') }}" class="sg-btn-primary">Continue Shopping →</a>
      <a href="{{ route('website.home') }}" class="sg-btn-outline">← Home</a>
    </div>

    <div style="margin-top:36px;padding:20px;background:var(--dark-800);border:1px solid rgba(0,191,176,.1);border-radius:4px">
      <div style="font-size:13px;color:var(--white-faint);line-height:1.8">
        📦 Your gem will be carefully packaged and shipped with full insurance.<br>
        📄 Your PayPal receipt has been emailed to your PayPal address.<br>
        💬 Questions? <a href="#" style="color:var(--teal-300)">Contact us on WhatsApp</a>
      </div>
    </div>

  </div>
</div>

@endsection
