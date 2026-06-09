@extends('website.layout')

@section('title', $product->display_website_title)
@section('meta_desc', Str::limit(strip_tags($product->display_website_description ?? ''), 160))

@section('content')

{{-- BREADCRUMB --}}
<div style="display:flex;align-items:center;gap:10px;padding:20px 60px;font-size:12px;color:var(--white-faint);border-bottom:1px solid rgba(0,191,176,.06);background:var(--dark-800)">
  <a href="{{ route('website.home') }}" style="text-decoration:none;color:var(--white-faint);transition:color .3s" onmouseenter="this.style.color='var(--teal-300)'" onmouseleave="this.style.color='var(--white-faint)'">Home</a>
  <span style="color:rgba(0,191,176,.3)">›</span>
  <a href="{{ route('website.collections') }}" style="text-decoration:none;color:var(--white-faint);transition:color .3s" onmouseenter="this.style.color='var(--teal-300)'" onmouseleave="this.style.color='var(--white-faint)'">Collections</a>
  @if($product->category)
  <span style="color:rgba(0,191,176,.3)">›</span>
  <a href="{{ route('website.collections', ['category' => strtolower($product->category->code ?? '')]) }}" style="text-decoration:none;color:var(--white-faint);transition:color .3s" onmouseenter="this.style.color='var(--teal-300)'" onmouseleave="this.style.color='var(--white-faint)'">{{ $product->category->name }}</a>
  @endif
  <span style="color:rgba(0,191,176,.3)">›</span>
  <span style="color:var(--teal-300)">{{ Str::limit($product->title, 50) }}</span>
</div>

{{-- PRODUCT DETAIL GRID --}}
<div style="display:grid;grid-template-columns:1fr 1fr;min-height:80vh;background:var(--dark-900)">

  {{-- LEFT: Gallery --}}
  <div style="background:var(--dark-800);position:relative">
    <div id="mainImgWrap" style="width:100%;height:520px;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;background:radial-gradient(circle at 40% 40%,rgba(0,191,176,.15),rgba(4,14,13,.98))">
      @if($product->primary_image_url)
        <img id="mainImg" src="{{ $product->primary_image_url }}" alt="{{ $product->title }}"
          style="max-width:90%;max-height:480px;object-fit:contain;transition:all .5s;filter:drop-shadow(0 0 32px rgba(0,191,176,.3))">
      @else
        <div id="mainImg" style="width:200px;height:200px;clip-path:polygon(50% 0%,93% 25%,93% 75%,50% 100%,7% 75%,7% 25%);background:linear-gradient(135deg,rgba(0,220,200,.9),rgba(0,150,140,.7));box-shadow:0 0 80px rgba(0,191,176,.65),inset 0 0 60px rgba(255,255,255,.1);animation:sg-detail-shimmer 3s ease infinite alternate"></div>
      @endif
      @if($product->certificate_number)
      <div style="position:absolute;top:18px;left:18px;display:flex;align-items:center;gap:7px;background:rgba(4,14,13,.85);border:1px solid rgba(0,191,176,.28);padding:7px 13px;border-radius:2px;font-size:12px;color:var(--teal-300);font-weight:500;backdrop-filter:blur(8px)">
        <span>✓</span> Certified
      </div>
      @endif
      <div style="position:absolute;bottom:18px;right:18px;display:flex;align-items:center;gap:7px;background:rgba(4,14,13,.8);border:1px solid rgba(0,191,176,.2);padding:7px 13px;border-radius:20px;font-size:11px;color:var(--teal-400);font-weight:500;letter-spacing:1px">
        <span style="animation:sg-spin-sm 3s linear infinite;font-size:15px">↻</span> 360° View
      </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:2px;background:var(--dark-900)">
      @php
        $gallery = $product->gallery_urls ?? [];
        $allImgs = array_merge(
          $product->primary_image_url ? [['url' => $product->primary_image_url, 'thumb' => $product->primary_thumb_url ?? $product->primary_image_url]] : [],
          $gallery
        );
      @endphp
      @forelse(array_slice($allImgs, 0, 4) as $i => $img)
      <div class="sg-thumb {{ $i===0 ? 'active' : '' }}" onclick="setMainImg('{{ $img['url'] }}', this)"
        style="aspect-ratio:1;cursor:pointer;background:var(--dark-750);display:flex;align-items:center;justify-content:center;overflow:hidden;border:2px solid {{ $i===0 ? 'var(--teal-400)' : 'transparent' }};transition:border-color .3s">
        <img src="{{ $img['thumb'] ?? $img['url'] }}" alt="" style="width:100%;height:100%;object-fit:cover;transition:opacity .3s" onmouseenter="this.style.opacity='.8'" onmouseleave="this.style.opacity='1'">
      </div>
      @empty
      @foreach(range(0,3) as $i)
      <div style="aspect-ratio:1;background:var(--dark-750);display:flex;align-items:center;justify-content:center;border:2px solid {{ $i===0 ? 'var(--teal-400)' : 'transparent' }}">
        <div style="width:36px;height:36px;clip-path:polygon(50% 0%,93% 25%,93% 75%,50% 100%,7% 75%,7% 25%);background:linear-gradient({{ $i*30 }}deg,rgba(0,191,176,.6),rgba(0,120,110,.4));opacity:{{ 1 - $i*0.2 }}"></div>
      </div>
      @endforeach
      @endforelse
    </div>
  </div>

  {{-- RIGHT: Product Info --}}
  <div style="padding:44px 56px;overflow-y:auto">

    <div style="font-size:11px;font-weight:500;letter-spacing:2px;text-transform:uppercase;color:var(--teal-400);margin-bottom:10px">
      {{ $product->category?->name ?? ($product->stone_type ?? 'Gemstone') }}
    </div>

    <h1 style="font-family:'Cormorant Garamond',serif;font-size:48px;font-weight:700;line-height:1.1;margin-bottom:14px">
      {{ $product->display_website_title }}
    </h1>

    @if($product->short_description)
    <p style="font-size:16px;color:var(--white-dim);margin-bottom:26px;line-height:1.7">{{ $product->short_description }}</p>
    @endif

    {{-- Price --}}
    <div style="display:flex;align-items:baseline;gap:14px;margin-bottom:28px">
      @if($product->website_price)
      <div style="font-family:'Cormorant Garamond',serif;font-size:44px;font-weight:700;color:var(--teal-300)">
        {{ $settings->formatPrice($product->website_price) }}
      </div>
      <div style="font-size:17px;color:var(--white-faint)">{{ strtoupper($settings->get('currency_code', 'USD')) }}</div>
      @else
      <div style="font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:600;color:var(--teal-300)">Price on Request</div>
      @endif
    </div>

    {{-- Specs Table --}}
    <div style="background:var(--dark-800);border:1px solid rgba(0,191,176,.1);border-radius:4px;margin-bottom:28px;overflow:hidden">
      <div style="padding:12px 18px;background:rgba(0,191,176,.055);border-bottom:1px solid rgba(0,191,176,.1);font-size:11px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--teal-400)">
        Gem Specifications
      </div>
      @php
        $specs = [
          ['label' => 'Carat Weight',    'value' => $product->carat_weight ? $product->carat_weight . ' ct' : null,  'highlight' => true],
          ['label' => 'Stone Type',      'value' => $product->stone_type,    'highlight' => false],
          ['label' => 'Colour Grade',    'value' => $product->colour_grade,  'highlight' => false],
          ['label' => 'Clarity Grade',   'value' => $product->clarity_grade, 'highlight' => false],
          ['label' => 'Cut / Shape',     'value' => $product->cut_shape,     'highlight' => false],
          ['label' => 'Country of Origin','value' => $product->country_of_origin, 'highlight' => true],
          ['label' => 'Treatment',       'value' => $product->treatment,     'highlight' => false],
          ['label' => 'Certificate No.', 'value' => $product->certificate_number, 'highlight' => false],
          ['label' => 'SKU',             'value' => $product->sku,           'highlight' => false],
        ];
      @endphp
      @foreach($specs as $spec)
      @if($spec['value'])
      <div style="display:grid;grid-template-columns:1fr 1fr;padding:12px 18px;border-bottom:1px solid rgba(0,191,176,.055);transition:background .2s" onmouseenter="this.style.background='rgba(0,191,176,.03)'" onmouseleave="this.style.background=''">
        <span style="font-size:13px;color:var(--white-faint)">{{ $spec['label'] }}</span>
        <span style="font-size:13px;font-weight:500;{{ $spec['highlight'] ? 'color:var(--teal-300)' : 'color:#f0faf8' }}">{{ $spec['value'] }}</span>
      </div>
      @endif
      @endforeach
    </div>

    {{-- Cert Badges --}}
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:28px">
      @if($product->certificate_number)
      <div style="display:flex;align-items:center;gap:7px;border:1px solid rgba(0,191,176,.22);background:rgba(0,191,176,.05);padding:8px 14px;border-radius:2px;font-size:12px;color:var(--teal-300);font-weight:500">✓ Certified</div>
      @endif
      @if($product->treatment === 'None' || $product->treatment === null)
      <div style="display:flex;align-items:center;gap:7px;border:1px solid rgba(0,191,176,.22);background:rgba(0,191,176,.05);padding:8px 14px;border-radius:2px;font-size:12px;color:var(--teal-300);font-weight:500">✦ Unheated</div>
      @endif
      <div style="display:flex;align-items:center;gap:7px;border:1px solid rgba(0,191,176,.22);background:rgba(0,191,176,.05);padding:8px 14px;border-radius:2px;font-size:12px;color:var(--teal-300);font-weight:500">🌿 Ethically Sourced</div>
      <div style="display:flex;align-items:center;gap:7px;border:1px solid rgba(0,191,176,.22);background:rgba(0,191,176,.05);padding:8px 14px;border-radius:2px;font-size:12px;color:var(--teal-300);font-weight:500">📦 Insured Shipping</div>
    </div>

    {{-- CTA --}}
    <div style="display:flex;gap:10px;margin-bottom:28px">
      @if($settings->bool('cart_enabled', true) && $product->website_price)
        <button id="addToCartBtn"
          onclick="addToCart({{ $product->id }}, this)"
          style="flex:1;background:var(--teal-500);color:#fff;border:none;cursor:pointer;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:2px;text-transform:uppercase;padding:16px;border-radius:2px;transition:all .3s"
          onmouseenter="this.style.background='var(--teal-400)'" onmouseleave="this.style.background='var(--teal-500)'">
          + Add to Cart
        </button>
        @if($settings->bool('checkout_enabled', true))
        <a href="{{ route('website.checkout') }}"
          style="padding:16px 20px;background:transparent;border:1px solid rgba(0,191,176,.3);color:var(--teal-300);font-family:'Jost',sans-serif;font-size:12px;letter-spacing:1.5px;text-transform:uppercase;border-radius:2px;text-decoration:none;display:inline-flex;align-items:center;transition:all .3s"
          onmouseenter="this.style.background='rgba(0,191,176,.08)';this.style.borderColor='var(--teal-400)'" onmouseleave="this.style.background='';this.style.borderColor='rgba(0,191,176,.3)'">
          Checkout
        </a>
        @endif
      @else
        <button onclick="alert('Enquiry noted! Our team will contact you shortly.')"
          style="flex:1;background:var(--teal-500);color:#fff;border:none;cursor:pointer;font-family:'Jost',sans-serif;font-size:13px;font-weight:500;letter-spacing:2px;text-transform:uppercase;padding:16px;border-radius:2px;transition:all .3s"
          onmouseenter="this.style.background='var(--teal-400)'" onmouseleave="this.style.background='var(--teal-500)'">
          Enquire Now
        </button>
      @endif
      <button title="Save to wishlist"
        style="width:52px;background:transparent;border:1px solid rgba(0,191,176,.3);color:var(--teal-300);font-size:20px;cursor:pointer;border-radius:2px;transition:all .3s"
        onmouseenter="this.style.background='rgba(0,191,176,.08)';this.style.borderColor='var(--teal-400)'" onmouseleave="this.style.background='';this.style.borderColor='rgba(0,191,176,.3)'">♡</button>
    </div>

    {{-- Tabs --}}
    <div style="display:flex;gap:0;border-bottom:1px solid rgba(0,191,176,.14);margin-bottom:20px">
      @foreach(['story' => 'Story', 'shipping' => 'Shipping', 'returns' => 'Returns'] as $key => $label)
      <button onclick="switchTab('{{ $key }}', this)"
        id="tab-btn-{{ $key }}"
        style="padding:11px 22px;font-size:13px;font-weight:500;letter-spacing:1px;text-transform:uppercase;color:{{ $key==='story' ? 'var(--teal-300)' : 'var(--white-faint)' }};cursor:pointer;border:none;background:none;border-bottom:2px solid {{ $key==='story' ? 'var(--teal-400)' : 'transparent' }};margin-bottom:-1px;transition:all .3s">
        {{ $label }}
      </button>
      @endforeach
    </div>
    <div id="tab-story" style="font-size:14px;line-height:1.8;color:var(--white-dim)">
      {!! nl2br(e($product->display_website_description ?? $product->full_description ?? 'A fine quality gemstone, hand-selected for authenticity and brilliance.')) !!}
    </div>
    <div id="tab-shipping" style="font-size:14px;line-height:1.8;color:var(--white-dim);display:none">
      All gems are shipped fully insured via DHL Express or FedEx International Priority. Standard delivery: 3–5 business days worldwide. Free insured shipping on all orders over {{ $settings->formatPrice(50000) }}.
    </div>
    <div id="tab-returns" style="font-size:14px;line-height:1.8;color:var(--white-dim);display:none">
      We offer a 7-day return window from the date of delivery. The stone must be returned in its original packaging with all accompanying certificates. Custom orders and bespoke items are non-refundable.
    </div>

  </div>
</div>

{{-- Related Products --}}
@if($relatedProducts->isNotEmpty())
<section style="padding:72px 60px;background:var(--dark-850)">
  <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:36px" class="sg-reveal">
    <div>
      <div class="sg-eyebrow">You May Also Like</div>
      <h2 class="sg-section-title">Related <em>Gems</em></h2>
    </div>
    <a href="{{ route('website.collections') }}" class="sg-btn-outline">View All</a>
  </div>
  <div class="sg-product-grid sg-reveal">
    @foreach($relatedProducts as $product)
    @include('website._product_card', ['product' => $product])
    @endforeach
  </div>
</section>
@endif

@endsection

@push('head_styles')
<style>
@keyframes sg-detail-shimmer {
  from { box-shadow: 0 0 60px rgba(0,191,176,.6), inset 0 0 40px rgba(255,255,255,.1); }
  to   { box-shadow: 0 0 100px rgba(0,191,176,.9), inset 0 0 60px rgba(255,255,255,.2); }
}
@keyframes sg-spin-sm {
  from { display:inline-block; transform: rotate(0deg); }
  to   { display:inline-block; transform: rotate(360deg); }
}
</style>
@endpush

@push('scripts')
<script>
function setMainImg(url, el) {
  var img = document.getElementById('mainImg');
  if (img && img.tagName === 'IMG') {
    img.style.opacity = '0';
    setTimeout(function() { img.src = url; img.style.opacity = '1'; }, 200);
  }
  document.querySelectorAll('.sg-thumb').forEach(function (t) {
    t.style.borderColor = 'transparent';
  });
  el.style.borderColor = 'var(--teal-400)';
}
function switchTab(name, btn) {
  ['story','shipping','returns'].forEach(function (t) {
    document.getElementById('tab-' + t).style.display = (t === name ? 'block' : 'none');
    var b = document.getElementById('tab-btn-' + t);
    if (b) {
      b.style.color = t === name ? 'var(--teal-300)' : 'var(--white-faint)';
      b.style.borderBottomColor = t === name ? 'var(--teal-400)' : 'transparent';
    }
  });
}
</script>
@endpush
