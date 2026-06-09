<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', $settings->get('site_name', 'Sukaina Gems')) — Rare & Precious Stones</title>
<meta name="description" content="@yield('meta_desc', $settings->get('site_tagline', 'Specialists in Paraiba Tourmaline and Tanzanite. 5+ years of precious and semi-precious gems.'))">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{
  --teal-300:#40cfbf;--teal-400:#00bfb0;--teal-500:#00b0a0;
  --teal-600:#009e8f;--teal-700:#008778;--teal-800:#00716a;
  --dark-950:#040e0d;--dark-900:#071410;--dark-850:#0a1c17;
  --dark-800:#0d241e;--dark-750:#112e26;--dark-700:#163a30;
  --gold:#c9a84c;--gold-light:#e2c479;
  --white:#f0faf8;--white-dim:rgba(240,250,248,.7);
  --white-faint:rgba(240,250,248,.35);--white-ghost:rgba(240,250,248,.08);
  --shadow-teal:rgba(0,191,176,.25);
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{background:var(--dark-900);color:var(--white);font-family:'Jost',sans-serif;font-weight:300;overflow-x:hidden}

/* NAV */
.sg-nav{position:fixed;top:0;left:0;right:0;z-index:1000;display:flex;align-items:center;justify-content:space-between;padding:0 60px;height:64px;background:rgba(7,20,16,.92);backdrop-filter:blur(20px);border-bottom:1px solid rgba(0,191,176,.12);transition:all .3s}
.sg-nav.scrolled{height:56px;background:rgba(7,20,16,.98);border-bottom-color:rgba(0,191,176,.22)}
.sg-logo{display:flex;align-items:center;gap:10px;text-decoration:none}
.sg-logo-icon{width:36px;height:36px;background:linear-gradient(135deg,var(--teal-400),var(--teal-700));border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;box-shadow:0 0 16px rgba(0,191,176,.4)}
.sg-logo-text{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;color:var(--white);letter-spacing:2px;text-transform:uppercase}
.sg-nav-links{display:flex;align-items:center;gap:36px}
.sg-nav-links a{text-decoration:none;color:var(--white-dim);font-size:13px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;position:relative;transition:color .3s}
.sg-nav-links a::after{content:'';position:absolute;bottom:-4px;left:0;right:0;height:1px;background:var(--teal-300);transform:scaleX(0);transition:transform .3s}
.sg-nav-links a:hover,.sg-nav-links a.active{color:var(--teal-300)}
.sg-nav-links a:hover::after,.sg-nav-links a.active::after{transform:scaleX(1)}
.sg-nav-right{display:flex;gap:8px;align-items:center}
.sg-icon-btn{background:none;border:none;cursor:pointer;color:var(--white-dim);font-size:18px;width:38px;height:38px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:all .3s;position:relative;text-decoration:none}
.sg-icon-btn:hover{color:var(--teal-300);background:var(--white-ghost)}

/* Cart badge */
.sg-cart-badge{position:absolute;top:3px;right:3px;width:16px;height:16px;border-radius:50%;background:var(--teal-500);color:#fff;font-size:10px;font-weight:600;display:flex;align-items:center;justify-content:center;line-height:1}

/* Cart Drawer */
.sg-cart-drawer{position:fixed;top:0;right:0;bottom:0;width:380px;background:var(--dark-800);border-left:1px solid rgba(0,191,176,.15);z-index:2000;transform:translateX(100%);transition:transform .35s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column}
.sg-cart-drawer.open{transform:translateX(0)}
.sg-drawer-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1999;opacity:0;pointer-events:none;transition:opacity .35s}
.sg-drawer-overlay.open{opacity:1;pointer-events:all}
.sg-drawer-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid rgba(0,191,176,.1)}
.sg-drawer-body{flex:1;overflow-y:auto;padding:16px 24px}
.sg-drawer-footer{padding:20px 24px;border-top:1px solid rgba(0,191,176,.1)}
.sg-drawer-item{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid rgba(0,191,176,.06)}
.sg-drawer-close{background:none;border:none;cursor:pointer;color:var(--white-dim);font-size:20px;transition:color .3s}
.sg-drawer-close:hover{color:var(--teal-300)}

/* MAIN */
.sg-main{padding-top:64px;min-height:100vh}

/* FOOTER */
.sg-footer{background:var(--dark-950);border-top:1px solid rgba(0,191,176,.08);padding:56px 60px 0}
.sg-footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1.5fr;gap:48px;padding-bottom:48px;border-bottom:1px solid rgba(0,191,176,.08)}
.sg-footer-brand-name{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--white);margin-bottom:10px}
.sg-footer-brand-name span{color:var(--teal-300)}
.sg-footer-tagline{font-size:13px;color:var(--white-faint);line-height:1.7;margin-bottom:20px;max-width:280px}
.sg-footer-social{display:flex;gap:8px}
.sg-social-btn{width:34px;height:34px;border-radius:50%;border:1px solid rgba(0,191,176,.2);display:flex;align-items:center;justify-content:center;color:var(--teal-400);font-size:13px;text-decoration:none;transition:all .3s}
.sg-social-btn:hover{border-color:var(--teal-400);background:rgba(0,191,176,.1)}
.sg-footer-heading{font-size:11px;font-weight:600;letter-spacing:2.5px;text-transform:uppercase;color:var(--white);margin-bottom:18px}
.sg-footer-links{list-style:none}
.sg-footer-links li{margin-bottom:10px}
.sg-footer-links a{text-decoration:none;font-size:14px;color:var(--white-faint);transition:color .3s}
.sg-footer-links a:hover{color:var(--teal-300)}
.sg-footer-bottom{display:flex;align-items:center;justify-content:space-between;padding:18px 0;font-size:12px;color:rgba(240,250,248,.22)}
.sg-newsletter-input-wrap{display:flex;border:1px solid rgba(0,191,176,.25);border-radius:2px;overflow:hidden}
.sg-newsletter-input{flex:1;background:rgba(7,20,16,.8);border:none;color:var(--white);font-family:'Jost',sans-serif;font-size:13px;padding:11px 16px;outline:none}
.sg-newsletter-input::placeholder{color:var(--white-faint)}
.sg-newsletter-btn{background:var(--teal-500);color:#fff;border:none;cursor:pointer;padding:11px 18px;font-size:14px;transition:background .3s}
.sg-newsletter-btn:hover{background:var(--teal-400)}

/* SCROLLBAR */
::-webkit-scrollbar{width:6px}
::-webkit-scrollbar-track{background:var(--dark-900)}
::-webkit-scrollbar-thumb{background:rgba(0,191,176,.3);border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:rgba(0,191,176,.5)}

/* UTILITIES */
.sg-container{max-width:1400px;margin:0 auto;padding:0 60px}
.sg-section{padding:80px 0}
.sg-eyebrow{font-size:11px;font-weight:500;letter-spacing:3px;text-transform:uppercase;color:var(--teal-400);margin-bottom:10px}
.sg-section-title{font-family:'Cormorant Garamond',serif;font-size:44px;font-weight:600;line-height:1.15;color:var(--white)}
.sg-section-title em{color:var(--teal-300);font-style:italic}
.sg-btn-primary{display:inline-flex;align-items:center;gap:8px;background:var(--teal-500);color:#fff;font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;text-decoration:none;padding:13px 28px;border-radius:2px;border:none;cursor:pointer;transition:all .3s;position:relative;overflow:hidden}
.sg-btn-primary::before{content:'';position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.1),transparent);transform:translateX(-100%);transition:transform .5s}
.sg-btn-primary:hover::before{transform:translateX(100%)}
.sg-btn-primary:hover{background:var(--teal-400);transform:translateY(-2px);box-shadow:0 8px 24px var(--shadow-teal)}
.sg-btn-outline{display:inline-flex;align-items:center;gap:8px;background:transparent;color:var(--teal-300);font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;text-decoration:none;padding:12px 28px;border-radius:2px;border:1px solid rgba(0,191,176,.35);cursor:pointer;transition:all .3s}
.sg-btn-outline:hover{border-color:var(--teal-300);background:rgba(0,191,176,.07);transform:translateY(-2px)}

/* PRODUCT CARDS */
.sg-product-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:2px}
.sg-product-card{background:var(--dark-800);cursor:pointer;transition:all .3s;position:relative;overflow:hidden;border:1px solid rgba(0,191,176,.06);text-decoration:none;color:inherit;display:block}
.sg-product-card:hover{border-color:rgba(0,191,176,.22);transform:translateY(-3px);box-shadow:0 12px 40px rgba(0,0,0,.5)}
.sg-product-img{position:relative;aspect-ratio:1;overflow:hidden;background:var(--dark-750)}
.sg-product-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s}
.sg-product-img-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:radial-gradient(circle at 40% 50%,rgba(0,191,176,.18),var(--dark-750))}
.sg-gem-hex{width:70px;height:70px;clip-path:polygon(50% 0%,93% 25%,93% 75%,50% 100%,7% 75%,7% 25%);background:linear-gradient(135deg,var(--teal-300),var(--teal-700));filter:drop-shadow(0 0 16px rgba(0,191,176,.6));transition:all .4s}
.sg-product-card:hover .sg-product-img img{transform:scale(1.06)}
.sg-product-card:hover .sg-gem-hex{transform:rotate(15deg) scale(1.15);filter:drop-shadow(0 0 28px rgba(0,191,176,.85))}
.sg-product-badge{position:absolute;top:10px;left:10px;font-size:10px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;padding:4px 9px;border-radius:2px}
.sg-badge-gia{background:rgba(0,191,176,.12);color:var(--teal-300);border:1px solid rgba(0,191,176,.28)}
.sg-badge-rare{background:rgba(201,168,76,.12);color:var(--gold-light);border:1px solid rgba(201,168,76,.28)}
.sg-badge-new{background:rgba(80,200,130,.12);color:#7ec87e;border:1px solid rgba(80,200,130,.25)}
.sg-badge-hot{background:rgba(220,80,80,.12);color:#e07070;border:1px solid rgba(220,80,80,.25)}
.sg-product-body{padding:18px}
.sg-product-name{font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:600;color:var(--white);margin-bottom:5px;line-height:1.3}
.sg-product-meta{font-size:12px;color:var(--white-faint);margin-bottom:14px}
.sg-product-meta span{color:rgba(0,191,176,.55);margin:0 5px}
.sg-product-footer{display:flex;align-items:center;justify-content:space-between}
.sg-product-price{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:700;color:var(--teal-300)}
.sg-btn-add{background:var(--teal-600);color:#fff;border:none;cursor:pointer;font-size:11px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;padding:7px 14px;border-radius:2px;transition:all .3s;text-decoration:none;display:inline-block}
.sg-btn-add:hover{background:var(--teal-500);color:#fff}

/* REVEAL ANIMATION */
.sg-reveal{opacity:0;transform:translateY(28px);transition:opacity .65s ease,transform .65s ease}
.sg-reveal.visible{opacity:1;transform:translateY(0)}

/* DIVIDER */
.sg-divider{height:1px;background:linear-gradient(90deg,transparent,rgba(0,191,176,.18),transparent);margin:0 60px}

@media(max-width:1024px){
  .sg-product-grid{grid-template-columns:repeat(3,1fr)}
  .sg-nav{padding:0 24px}
  .sg-container{padding:0 24px}
  .sg-footer-grid{grid-template-columns:1fr 1fr;gap:32px}
  .sg-cart-drawer{width:100%}
}
@media(max-width:768px){
  .sg-product-grid{grid-template-columns:repeat(2,1fr)}
  .sg-nav-links{display:none}
}
</style>
@stack('head_styles')
</head>
<body>

{{-- NAVBAR --}}
<nav class="sg-nav" id="sgNav">
  <a class="sg-logo" href="{{ route('website.home') }}">
    <div class="sg-logo-icon">SG</div>
    <span class="sg-logo-text">{{ $settings->get('site_name', 'Sukaina Gems') }}</span>
  </a>
  <div class="sg-nav-links">
    <a href="{{ route('website.home') }}"        class="{{ request()->routeIs('website.home')        ? 'active' : '' }}">Home</a>
    <a href="{{ route('website.collections') }}" class="{{ request()->routeIs('website.collections') ? 'active' : '' }}">Collections</a>
    <a href="{{ route('website.collections', ['category' => 'paraiba']) }}">Paraiba</a>
    <a href="{{ route('website.collections', ['category' => 'tanzanite']) }}">Tanzanite</a>
    <a href="#">About</a>
    <a href="#">Contact</a>
  </div>
  <div class="sg-nav-right">
    <button class="sg-icon-btn" title="Search">🔍</button>
    <button class="sg-icon-btn" title="Account">👤</button>

    @if($settings->bool('cart_enabled', true))
    {{-- Cart icon with live badge --}}
    <button class="sg-icon-btn" id="sgCartBtn" title="Cart" onclick="openCartDrawer()">
      🛒
      @php $cartCount = count(session('sg_cart', [])); @endphp
      <span class="sg-cart-badge" id="sgCartBadge" style="{{ $cartCount > 0 ? '' : 'display:none' }}">{{ $cartCount }}</span>
    </button>
    @endif
  </div>
</nav>

{{-- CART DRAWER --}}
@if($settings->bool('cart_enabled', true))
<div class="sg-drawer-overlay" id="sgDrawerOverlay" onclick="closeCartDrawer()"></div>
<div class="sg-cart-drawer" id="sgCartDrawer">
  <div class="sg-drawer-header">
    <div style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600">
      Your Cart
      <span id="drawerCount" style="font-family:'Jost',sans-serif;font-size:13px;font-weight:400;color:var(--white-faint);margin-left:8px">({{ $cartCount }})</span>
    </div>
    <button class="sg-drawer-close" onclick="closeCartDrawer()">✕</button>
  </div>

  <div class="sg-drawer-body" id="drawerBody">
    @php $cart = session('sg_cart', []); @endphp
    @if(empty($cart))
      <div style="text-align:center;padding:60px 0;color:var(--white-faint)">
        <div style="font-size:40px;margin-bottom:14px">💎</div>
        <p style="font-size:14px">Your cart is empty.<br>Browse gems to add.</p>
      </div>
    @else
      @foreach($cart as $item)
      <div class="sg-drawer-item" data-id="{{ $item['id'] }}">
        <div style="width:48px;height:48px;flex-shrink:0;border-radius:2px;overflow:hidden;background:var(--dark-750)">
          @if($item['thumb'])<img src="{{ $item['thumb'] }}" alt="" style="width:100%;height:100%;object-fit:cover">
          @else<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center"><div style="width:24px;height:24px;clip-path:polygon(50% 0%,93% 25%,93% 75%,50% 100%,7% 75%,7% 25%);background:linear-gradient(135deg,var(--teal-300),var(--teal-700))"></div></div>@endif
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-size:13px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $item['title'] }}</div>
          <div style="font-size:12px;color:var(--teal-300);margin-top:2px">{{ $settings->formatPrice($item['price']) }}</div>
        </div>
        <button onclick="drawerRemove({{ $item['id'] }}, this)" style="background:none;border:none;cursor:pointer;color:var(--white-faint);font-size:14px;padding:4px;transition:color .3s" onmouseenter="this.style.color='#e07070'" onmouseleave="this.style.color='var(--white-faint)'" title="Remove">✕</button>
      </div>
      @endforeach
    @endif
  </div>

  <div class="sg-drawer-footer">
    @php $drawerTotal = array_sum(array_column($cart, 'subtotal')); @endphp
    <div style="display:flex;justify-content:space-between;margin-bottom:16px">
      <span style="font-size:14px;color:var(--white-dim)">Total</span>
      <span id="drawerTotal" style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:var(--teal-300)">{{ $settings->formatPrice($drawerTotal) }}</span>
    </div>
    <a href="{{ route('website.cart.index') }}" class="sg-btn-outline" style="display:flex;justify-content:center;margin-bottom:8px;font-size:11px">View Cart</a>
    @if($settings->bool('checkout_enabled', true) && count($cart) > 0)
    <a href="{{ route('website.checkout.index') }}" class="sg-btn-primary" style="display:flex;justify-content:center">Checkout →</a>
    @endif
  </div>
</div>
@endif

<main class="sg-main">
  @yield('content')
</main>

{{-- FOOTER --}}
<footer class="sg-footer">
  <div class="sg-footer-grid">
    <div>
      <div class="sg-footer-brand-name">✦ <span>{{ $settings->get('site_name', 'Sukaina') }}</span></div>
      <p class="sg-footer-tagline">{{ $settings->get('site_tagline', 'Specialists in Paraiba Tourmaline and Tanzanite.') }}</p>
      <div class="sg-footer-social">
        <a class="sg-social-btn" href="#" aria-label="Instagram">ig</a>
        <a class="sg-social-btn" href="#" aria-label="Facebook">f</a>
        <a class="sg-social-btn" href="#" aria-label="Twitter">𝕏</a>
        @if($settings->get('contact_whatsapp'))
        <a class="sg-social-btn" href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings->get('contact_whatsapp')) }}" aria-label="WhatsApp">💬</a>
        @endif
      </div>
    </div>
    <div>
      <div class="sg-footer-heading">Shop</div>
      <ul class="sg-footer-links">
        <li><a href="{{ route('website.home') }}">Home</a></li>
        <li><a href="{{ route('website.collections') }}">All Gems</a></li>
        <li><a href="{{ route('website.collections', ['category' => 'paraiba']) }}">Paraiba Tourmaline</a></li>
        <li><a href="{{ route('website.collections', ['category' => 'tanzanite']) }}">Tanzanite</a></li>
      </ul>
    </div>
    <div>
      <div class="sg-footer-heading">Connect</div>
      <ul class="sg-footer-links">
        <li><a href="#">About Us</a></li>
        @if($settings->get('contact_email'))<li><a href="mailto:{{ $settings->get('contact_email') }}">Email Us</a></li>@endif
        @if($settings->get('contact_whatsapp'))<li><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings->get('contact_whatsapp')) }}">WhatsApp</a></li>@endif
        <li><a href="#">Privacy Policy</a></li>
      </ul>
    </div>
    <div>
      <div class="sg-footer-heading">Newsletter</div>
      <p style="font-size:13px;color:var(--white-faint);margin-bottom:14px;line-height:1.6;">New arrivals, rare finds, trade fair dates.</p>
      <div class="sg-newsletter-input-wrap">
        <input class="sg-newsletter-input" type="email" placeholder="Email address">
        <button class="sg-newsletter-btn">→</button>
      </div>
    </div>
  </div>
  <div class="sg-footer-bottom">
    <span>© {{ date('Y') }} {{ $settings->get('site_name', 'Sukaina Gems') }}. All rights reserved.</span>
    <span>Privacy Policy &nbsp;·&nbsp; Terms of Service</span>
  </div>
</footer>

<script>
// Navbar scroll
window.addEventListener('scroll', function () {
  document.getElementById('sgNav').classList.toggle('scrolled', window.scrollY > 50);
});

// Scroll reveal
(function () {
  var els = document.querySelectorAll('.sg-reveal');
  var obs = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) { if (e.isIntersecting) e.target.classList.add('visible'); });
  }, { threshold: 0.08, rootMargin: '0px 0px -32px 0px' });
  els.forEach(function (el) { obs.observe(el); });
})();

// ── Cart Drawer ────────────────────────────────────────────────────
var CSRF = (document.querySelector('meta[name=csrf-token]') || {}).getAttribute && document.querySelector('meta[name=csrf-token]').getAttribute('content');

function openCartDrawer() {
  document.getElementById('sgCartDrawer').classList.add('open');
  document.getElementById('sgDrawerOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeCartDrawer() {
  document.getElementById('sgCartDrawer').classList.remove('open');
  document.getElementById('sgDrawerOverlay').classList.remove('open');
  document.body.style.overflow = '';
}

function updateCartBadge(count) {
  var badge = document.getElementById('sgCartBadge');
  var dcnt  = document.getElementById('drawerCount');
  if (badge) { badge.textContent = count; badge.style.display = count > 0 ? '' : 'none'; }
  if (dcnt)  { dcnt.textContent = '(' + count + ')'; }
}

/**
 * Add to cart — called from product cards.
 * After success opens the drawer.
 */
function addToCart(productId, btnEl) {
  if (!CSRF) return;
  if (btnEl) { btnEl.disabled = true; btnEl.textContent = '…'; }

  fetch('{{ route("website.cart.add") }}', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify({ product_id: productId }),
  })
  .then(function (r) { return r.json(); })
  .then(function (d) {
    if (d.success) {
      updateCartBadge(d.count);
      openCartDrawer();
      // Reload drawer body via full page data (simple approach)
      reloadDrawerBody();
    } else {
      alert(d.message || 'Could not add to cart.');
    }
  })
  .catch(function () { alert('Could not add to cart.'); })
  .finally(function () {
    if (btnEl) { btnEl.disabled = false; btnEl.textContent = '+ Cart'; }
  });
}

function drawerRemove(productId, btn) {
  var row = btn.closest('.sg-drawer-item');
  if (row) { row.style.opacity = '0.4'; row.style.pointerEvents = 'none'; }
  fetch('{{ route("website.cart.remove") }}', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify({ product_id: productId }),
  })
  .then(function (r) { return r.json(); })
  .then(function (d) {
    if (d.success) {
      updateCartBadge(d.count);
      reloadDrawerBody();
    } else if (row) {
      row.style.opacity = '1'; row.style.pointerEvents = '';
    }
  });
}

function reloadDrawerBody() {
  // Lightweight: fetch the cart count endpoint and reload body via cart-data endpoint
  fetch('{{ route("website.cart.data") }}', { headers: { 'Accept': 'application/json' } })
  .then(function (r) { return r.json(); })
  .then(function (d) {
    document.getElementById('drawerBody').innerHTML = d.html || '';
    if (document.getElementById('drawerTotal')) {
      document.getElementById('drawerTotal').textContent = d.total || '';
    }
    updateCartBadge(d.count);
  });
}
</script>
@stack('scripts')
</body>
</html>
