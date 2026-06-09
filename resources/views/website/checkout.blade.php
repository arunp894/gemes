@extends('website.layout')

@section('title', 'Checkout')
@section('meta_desc', 'Complete your purchase securely with PayPal.')

@section('content')

{{-- HERO BAND --}}
<div style="height:180px;display:flex;flex-direction:column;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--dark-900),var(--dark-750));border-bottom:1px solid rgba(0,191,176,.1);text-align:center;position:relative;overflow:hidden">
  <div style="position:absolute;inset:0;background-image:linear-gradient(rgba(0,191,176,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(0,191,176,.025) 1px,transparent 1px);background-size:60px 60px"></div>
  <div style="position:relative;z-index:1">
    <div class="sg-eyebrow" style="text-align:center;margin-bottom:8px">Secure Payment</div>
    <h1 style="font-family:'Cormorant Garamond',serif;font-size:48px;font-weight:700">Check<em style="color:var(--teal-300);font-style:italic">out</em></h1>
  </div>
</div>

<div style="padding:60px;background:var(--dark-900);min-height:70vh">
  <div style="display:grid;grid-template-columns:1fr 380px;gap:40px;align-items:start">

    {{-- LEFT: Payment --}}
    <div>
      <h3 style="font-family:'Cormorant Garamond',serif;font-size:28px;margin-bottom:24px">Payment</h3>

      @if($paypalEnabled && $paypalClientId)
        {{-- Status message --}}
        <div id="paypalMsg" style="display:none;padding:16px;border-radius:4px;margin-bottom:20px;font-size:14px"></div>

        {{-- PayPal button container --}}
        <div style="background:var(--dark-800);border:1px solid rgba(0,191,176,.12);border-radius:4px;padding:28px">
          <div style="font-size:13px;color:var(--white-faint);margin-bottom:18px;display:flex;align-items:center;gap:8px">
            <span style="width:20px;height:20px;border-radius:50%;background:rgba(0,191,176,.12);display:inline-flex;align-items:center;justify-content:center;font-size:11px;color:var(--teal-300)">🔒</span>
            Your payment is securely processed by PayPal. We never store your card details.
          </div>

          <div id="paypal-button-container" style="min-height:48px"></div>

          {{-- Loading state --}}
          <div id="paypalLoading" style="text-align:center;padding:20px;color:var(--white-faint);font-size:13px">
            <div style="animation:sg-spin-sm 1s linear infinite;display:inline-block;font-size:20px;margin-bottom:8px">⟳</div><br>
            Loading PayPal…
          </div>
        </div>

      @elseif($paypalEnabled && !$paypalClientId)
        <div style="padding:24px;background:rgba(220,80,80,.08);border:1px solid rgba(220,80,80,.2);border-radius:4px;color:#e07070">
          <strong>Configuration Error:</strong> PayPal Client ID is not set. Please contact the store.
        </div>

      @else
        <div style="padding:24px;background:rgba(0,191,176,.06);border:1px solid rgba(0,191,176,.15);border-radius:4px">
          <p style="font-size:15px;color:var(--white-dim);margin-bottom:16px">
            Online payment is not yet enabled. Please contact us to complete your purchase.
          </p>
          @php $whatsapp = $settings->get('contact_whatsapp'); $email = $settings->get('contact_email'); @endphp
          <div style="display:flex;gap:12px;flex-wrap:wrap">
            @if($whatsapp)
              <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" class="sg-btn-primary" style="font-size:12px">
                💬 WhatsApp Us
              </a>
            @endif
            @if($email)
              <a href="mailto:{{ $email }}" class="sg-btn-outline" style="font-size:12px">
                ✉ Email Us
              </a>
            @endif
          </div>
        </div>
      @endif

      {{-- Trust badges --}}
      <div style="display:flex;gap:16px;margin-top:28px;flex-wrap:wrap">
        @foreach(['🔒 SSL Secured', '🌿 Ethically Sourced', '📦 Insured Shipping', '7-Day Returns'] as $badge)
        <div style="font-size:12px;color:var(--white-faint);display:flex;align-items:center;gap:6px">{{ $badge }}</div>
        @endforeach
      </div>
    </div>

    {{-- RIGHT: Order Summary --}}
    <div style="background:var(--dark-800);border:1px solid rgba(0,191,176,.12);border-radius:4px;padding:28px;position:sticky;top:80px">
      <h4 style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;margin-bottom:20px">Order Summary</h4>

      {{-- Items --}}
      <div style="max-height:320px;overflow-y:auto;margin-bottom:16px;padding-right:4px">
        @foreach($cart as $item)
        <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(0,191,176,.06)">
          <div style="width:44px;height:44px;flex-shrink:0;border-radius:2px;overflow:hidden;background:var(--dark-750)">
            @if($item['thumb'])
              <img src="{{ $item['thumb'] }}" alt="" style="width:100%;height:100%;object-fit:cover">
            @else
              <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">
                <div style="width:22px;height:22px;clip-path:polygon(50% 0%,93% 25%,93% 75%,50% 100%,7% 75%,7% 25%);background:linear-gradient(135deg,var(--teal-300),var(--teal-700))"></div>
              </div>
            @endif
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:500;color:var(--white);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $item['title'] }}</div>
            @if(!empty($item['carat']))<div style="font-size:11px;color:var(--white-faint)">{{ $item['carat'] }} ct</div>@endif
          </div>
          <div style="font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:700;color:var(--teal-300);flex-shrink:0">
            {{ $settings->formatPrice($item['price']) }}
          </div>
        </div>
        @endforeach
      </div>

      {{-- Totals --}}
      <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--white-dim);margin-bottom:8px">
        <span>Subtotal</span><span>{{ $settings->formatPrice($total) }}</span>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--white-faint);margin-bottom:16px">
        <span>Shipping</span><span style="color:var(--teal-300)">Calculated</span>
      </div>
      <div style="height:1px;background:rgba(0,191,176,.1);margin-bottom:16px"></div>
      <div style="display:flex;justify-content:space-between;margin-bottom:20px">
        <span style="font-size:15px;font-weight:500">Total</span>
        <span style="font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:var(--teal-300)">
          {{ $settings->formatPrice($total) }}
        </span>
      </div>

      <a href="{{ route('website.cart.index') }}" style="font-size:12px;color:var(--white-faint);text-decoration:none;transition:color .3s" onmouseenter="this.style.color='var(--teal-300)'" onmouseleave="this.style.color='var(--white-faint)'">
        ← Edit cart
      </a>
    </div>

  </div>
</div>

@endsection

@if($paypalEnabled && $paypalClientId)
@push('scripts')
<script>
  // Hide loading once SDK is ready
  function onPayPalLoad() {
    document.getElementById('paypalLoading').style.display = 'none';
  }
</script>
{{-- PayPal JS SDK: currency from settings, client-id from settings --}}
<script
  src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency={{ $currencyCode }}"
  onload="onPayPalLoad()"
  onerror="document.getElementById('paypalLoading').innerHTML='Failed to load PayPal. Check your internet connection or Client ID.'"
></script>
<script>
var CSRF = document.querySelector('meta[name=csrf-token]').getAttribute('content');

function showMsg(type, text) {
  var el = document.getElementById('paypalMsg');
  var bg  = type === 'success' ? 'rgba(80,200,130,.08)' : 'rgba(220,80,80,.08)';
  var brd = type === 'success' ? 'rgba(80,200,130,.25)' : 'rgba(220,80,80,.25)';
  var col = type === 'success' ? '#7ec87e' : '#e07070';
  el.style.display = 'block';
  el.style.background = bg;
  el.style.border = '1px solid ' + brd;
  el.style.color  = col;
  el.textContent  = text;
}

paypal.Buttons({
  style: {
    layout: 'vertical',
    color:  'gold',
    shape:  'rect',
    label:  'pay',
    height: 48,
  },

  // Step 1 — Create order on our server
  createOrder: function (data, actions) {
    return fetch('{{ route("website.checkout.create") }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': CSRF,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    })
    .then(function (res) {
      if (!res.ok) return res.json().then(function (err) { throw new Error(err.error || 'Order creation failed.'); });
      return res.json();
    })
    .then(function (data) {
      if (data.error) throw new Error(data.error);
      return data.orderID;
    });
  },

  // Step 2 — Capture after buyer approves
  onApprove: function (data, actions) {
    showMsg('success', '⟳ Processing payment…');
    return fetch('{{ route("website.checkout.capture") }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': CSRF,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ orderID: data.orderID }),
    })
    .then(function (res) { return res.json(); })
    .then(function (d) {
      if (d.success) {
        showMsg('success', '✓ ' + d.message);
        setTimeout(function () { window.location.href = d.redirect; }, 1200);
      } else {
        showMsg('error', d.error || 'Payment failed. Please contact support.');
      }
    });
  },

  onError: function (err) {
    console.error('PayPal error:', err);
    showMsg('error', 'A PayPal error occurred. Please try again or contact us.');
  },

  onCancel: function () {
    showMsg('error', 'Payment cancelled. Your cart is still saved — try again when ready.');
  },

}).render('#paypal-button-container');
</script>
@endpush
@endif
