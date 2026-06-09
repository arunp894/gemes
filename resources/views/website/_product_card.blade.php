{{--
  Reusable product card partial for the website storefront.
  Variables:
    $product    — App\Models\Product (with media loaded)
    $badge      — optional override: 'gia'|'rare'|'new'|'hot'
    $badgeText  — optional override text
    $settings   — App\Services\SettingService (injected via View composer)
--}}
@php
  $imgUrl     = $product->primary_thumb_url ?? $product->primary_image_url;
  $badge      = $badge      ?? ($product->featured_product ? 'gia' : 'new');
  $badgeText  = $badgeText  ?? ($product->featured_product ? 'Featured' : 'New');
  $badgeClass = ['gia' => 'sg-badge-gia', 'rare' => 'sg-badge-rare', 'new' => 'sg-badge-new', 'hot' => 'sg-badge-hot'][$badge] ?? 'sg-badge-gia';
  $price      = $product->website_price ? $settings->formatPrice($product->website_price) : null;
  $cartEnabled     = $settings->bool('cart_enabled', true);
  $hasPrice        = (bool) $product->website_price;
@endphp

<div class="sg-product-card">
  {{-- Clickable image / name area --}}
  <a href="{{ route('website.product', $product) }}" style="text-decoration:none;color:inherit;display:block">
    <div class="sg-product-img">
      @if($imgUrl)
        <img src="{{ $imgUrl }}" alt="{{ $product->title }}" loading="lazy">
      @else
        <div class="sg-product-img-placeholder">
          <div class="sg-gem-hex"></div>
        </div>
      @endif
      <span class="sg-product-badge {{ $badgeClass }}">{{ $badgeText }}</span>
    </div>
    <div class="sg-product-body">
      <div class="sg-product-name">{{ $product->title }}</div>
      <div class="sg-product-meta">
        @if($product->carat_weight){{ $product->carat_weight }} ct<span>·</span>@endif
        @if($product->country_of_origin){{ $product->country_of_origin }}@endif
      </div>
    </div>
  </a>

  {{-- Footer: price + action button --}}
  <div class="sg-product-body" style="padding-top:0">
    <div class="sg-product-footer">
      <div class="sg-product-price">{{ $price ?? 'Enquire' }}</div>

      @if($cartEnabled && $hasPrice)
        {{-- Add to Cart button — calls JS addToCart(), does NOT navigate away --}}
        <button
          onclick="event.stopPropagation(); addToCart({{ $product->id }}, this)"
          class="sg-btn-add"
          style="cursor:pointer;background:var(--teal-600);color:#fff;border:none"
          title="Add to cart">
          + Cart
        </button>
      @else
        {{-- Fallback: go to product page --}}
        <a href="{{ route('website.product', $product) }}" class="sg-btn-add">View →</a>
      @endif
    </div>
  </div>
</div>
