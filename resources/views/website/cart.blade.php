@extends('website.layout')

@section('title', 'Your Cart')
@section('meta_desc', 'Review your selected gems before checkout.')

@section('content')

{{-- HERO BAND --}}
<div style="height:200px;display:flex;flex-direction:column;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--dark-900),var(--dark-750));border-bottom:1px solid rgba(0,191,176,.1);text-align:center;position:relative;overflow:hidden">
  <div style="position:absolute;inset:0;background-image:linear-gradient(rgba(0,191,176,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(0,191,176,.025) 1px,transparent 1px);background-size:60px 60px"></div>
  <div style="position:relative;z-index:1">
    <div class="sg-eyebrow" style="text-align:center;margin-bottom:10px">Your Selection</div>
    <h1 style="font-family:'Cormorant Garamond',serif;font-size:52px;font-weight:700">Shopping <em style="color:var(--teal-300);font-style:italic">Cart</em></h1>
  </div>
</div>

<div style="padding:60px;background:var(--dark-900);min-height:60vh">

  @if(empty($cart))
    {{-- Empty State --}}
    <div style="text-align:center;padding:80px 0">
      <div style="font-size:64px;margin-bottom:20px">💎</div>
      <h2 style="font-family:'Cormorant Garamond',serif;font-size:36px;margin-bottom:10px">Your cart is empty</h2>
      <p style="color:var(--white-faint);margin-bottom:28px">Browse our collection to find your perfect gem.</p>
      <a href="{{ route('website.collections') }}" class="sg-btn-primary">Shop Collections →</a>
    </div>
  @else
    <div style="display:grid;grid-template-columns:1fr 340px;gap:32px;align-items:start">

      {{-- Cart Items --}}
      <div id="cartItemsWrap">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
          <h3 style="font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:600">
            {{ count($cart) }} item{{ count($cart) !== 1 ? 's' : '' }}
          </h3>
          <button onclick="clearCart()" style="background:none;border:1px solid rgba(220,80,80,.3);color:rgba(220,80,80,.7);cursor:pointer;font-family:'Jost',sans-serif;font-size:12px;letter-spacing:1px;padding:7px 16px;border-radius:2px;transition:all .3s" onmouseenter="this.style.borderColor='rgba(220,80,80,.6)';this.style.color='#e07070'" onmouseleave="this.style.borderColor='rgba(220,80,80,.3)';this.style.color='rgba(220,80,80,.7)'">
            Clear All
          </button>
        </div>

        @foreach($cart as $item)
        <div class="sg-cart-item" data-id="{{ $item['id'] }}"
          style="display:flex;align-items:center;gap:20px;padding:20px;background:var(--dark-800);border:1px solid rgba(0,191,176,.08);border-radius:4px;margin-bottom:10px;transition:border-color .3s"
          onmouseenter="this.style.borderColor='rgba(0,191,176,.2)'" onmouseleave="this.style.borderColor='rgba(0,191,176,.08)'">

          {{-- Thumb --}}
          <div style="width:80px;height:80px;flex-shrink:0;border-radius:3px;overflow:hidden;background:var(--dark-750);display:flex;align-items:center;justify-content:center">
            @if($item['thumb'])
              <img src="{{ $item['thumb'] }}" alt="{{ $item['title'] }}" style="width:100%;height:100%;object-fit:cover">
            @else
              <div style="width:44px;height:44px;clip-path:polygon(50% 0%,93% 25%,93% 75%,50% 100%,7% 75%,7% 25%);background:linear-gradient(135deg,var(--teal-300),var(--teal-700))"></div>
            @endif
          </div>

          {{-- Details --}}
          <div style="flex:1;min-width:0">
            <div style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--white);margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $item['title'] }}</div>
            <div style="font-size:12px;color:var(--white-faint);display:flex;gap:12px">
              @if(!empty($item['carat']))<span>{{ $item['carat'] }} ct</span>@endif
              @if(!empty($item['sku']))<span style="color:rgba(0,191,176,.4)">·</span><span>{{ $item['sku'] }}</span>@endif
            </div>
          </div>

          {{-- Price --}}
          <div style="text-align:right;flex-shrink:0">
            <div style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:var(--teal-300)">
              {{ $settings->formatPrice($item['price']) }}
            </div>
            <div style="font-size:11px;color:var(--white-faint);margin-top:2px">Qty: 1</div>
          </div>

          {{-- Remove --}}
          <button onclick="removeItem({{ $item['id'] }}, this)"
            style="width:34px;height:34px;flex-shrink:0;background:none;border:1px solid rgba(220,80,80,.25);color:rgba(220,80,80,.6);cursor:pointer;border-radius:2px;font-size:16px;display:flex;align-items:center;justify-content:center;transition:all .3s"
            onmouseenter="this.style.background='rgba(220,80,80,.08)';this.style.borderColor='rgba(220,80,80,.5)';this.style.color='#e07070'"
            onmouseleave="this.style.background='';this.style.borderColor='rgba(220,80,80,.25)';this.style.color='rgba(220,80,80,.6)'"
            title="Remove">✕</button>

        </div>
        @endforeach
      </div>

      {{-- Order Summary --}}
      <div style="background:var(--dark-800);border:1px solid rgba(0,191,176,.12);border-radius:4px;padding:28px;position:sticky;top:80px">
        <h4 style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;margin-bottom:20px">Order Summary</h4>

        <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:14px;color:var(--white-dim)">
          <span>Items ({{ count($cart) }})</span>
          <span>{{ $settings->formatPrice($total) }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:13px;color:var(--white-faint)">
          <span>Shipping</span>
          <span style="color:var(--teal-300)">Calculated at checkout</span>
        </div>

        <div style="height:1px;background:rgba(0,191,176,.1);margin:16px 0"></div>

        <div style="display:flex;justify-content:space-between;margin-bottom:24px">
          <span style="font-size:16px;font-weight:500">Total</span>
          <span id="summaryTotal" style="font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:var(--teal-300)">{{ $settings->formatPrice($total) }}</span>
        </div>

        @if($settings->bool('checkout_enabled', true))
          <a href="{{ route('website.checkout.index') }}" class="sg-btn-primary" style="width:100%;justify-content:center;display:flex;margin-bottom:10px">
            Proceed to Checkout →
          </a>
        @endif

        <a href="{{ route('website.collections') }}" class="sg-btn-outline" style="width:100%;justify-content:center;display:flex;font-size:11px">
          ← Continue Shopping
        </a>

        <div style="margin-top:20px;padding:14px;background:rgba(0,191,176,.05);border:1px solid rgba(0,191,176,.1);border-radius:3px">
          <div style="font-size:11px;color:var(--white-faint);text-align:center;line-height:1.6">
            🔒 Secure checkout &nbsp;·&nbsp; 📦 Insured shipping &nbsp;·&nbsp; 7-day returns
          </div>
        </div>
      </div>

    </div>
  @endif

</div>
@endsection

@push('scripts')
<script>
var CSRF = document.querySelector('meta[name=csrf-token]').getAttribute('content');

function removeItem(productId, btn) {
  var wrap = btn.closest('.sg-cart-item');
  wrap.style.opacity = '0.4';
  wrap.style.pointerEvents = 'none';

  fetch('{{ route("website.cart.remove") }}', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify({ product_id: productId }),
  })
  .then(r => r.json())
  .then(function (d) {
    if (d.success) {
      wrap.remove();
      updateCartBadge(d.count);
      if (d.count === 0) {
        location.reload();
      }
    } else {
      wrap.style.opacity = '1';
      wrap.style.pointerEvents = '';
    }
  });
}

function clearCart() {
  if (!confirm('Clear all items from your cart?')) return;
  fetch('{{ route("website.cart.clear") }}', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
  })
  .then(r => r.json())
  .then(function (d) {
    if (d.success) { location.reload(); }
  });
}

function updateCartBadge(count) {
  var badge = document.getElementById('sgCartBadge');
  if (badge) { badge.textContent = count; badge.style.display = count > 0 ? '' : 'none'; }
}
</script>
@endpush
