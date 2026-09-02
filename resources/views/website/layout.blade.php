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
  --teal-300:#d6308c;--teal-400:#b81f79;--teal-500:#9c1c6b;
  --teal-600:#7a1656;--teal-700:#5a2a96;--teal-800:#3a1e7a;
  --dark-950:#ffffff;--dark-900:#fdfaff;--dark-850:#f8f2fa;
  --dark-800:#f3ecf6;--dark-750:#eee3f2;--dark-700:#e7d8ee;
  --gold:#c9a84c;--gold-light:#e2c479;
  --white:#241735;--white-dim:rgba(36,23,53,.75);
  --white-faint:rgba(36,23,53,.45);--white-ghost:rgba(36,23,53,.06);
  --shadow-teal:rgba(214,48,140,.3);
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{background:var(--dark-900);color:var(--white);font-family:'Jost',sans-serif;font-weight:300;overflow-x:hidden}

/* NAV */
.sg-nav{position:fixed;top:0;left:0;right:0;z-index:1000;display:flex;align-items:center;justify-content:space-between;padding:0 60px;height:64px;background:rgba(255,255,255,.85);backdrop-filter:blur(20px);border-bottom:1px solid rgba(214,48,140,.12);transition:all .3s}
.sg-nav.scrolled{height:56px;background:rgba(255,255,255,.97);border-bottom-color:rgba(214,48,140,.22)}
.sg-logo{display:flex;align-items:center;gap:10px;text-decoration:none}
.sg-logo-icon{width:36px;height:36px;background:linear-gradient(135deg,var(--teal-400),var(--teal-700));border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;box-shadow:0 0 16px rgba(214,48,140,.4)}
.sg-logo-img{height:36px;width:auto;max-width:160px;object-fit:contain}
.sg-logo-text{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;color:var(--white);letter-spacing:2px;text-transform:uppercase}
.sg-nav-links{display:flex;align-items:center;gap:36px}
.sg-nav-links a{text-decoration:none;color:var(--white-dim);font-size:13px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;position:relative;transition:color .3s}
.sg-nav-links a::after{content:'';position:absolute;bottom:-4px;left:0;right:0;height:1px;background:var(--teal-300);transform:scaleX(0);transition:transform .3s}
.sg-nav-links a:hover,.sg-nav-links a.active{color:var(--teal-300)}
.sg-nav-links a:hover::after,.sg-nav-links a.active::after{transform:scaleX(1)}
.sg-nav-right{display:flex;gap:8px;align-items:center}
.sg-icon-btn{background:none;border:none;cursor:pointer;color:var(--white-dim);font-size:18px;width:38px;height:38px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:all .3s;position:relative;text-decoration:none}
.sg-icon-btn:hover{color:var(--teal-300);background:var(--white-ghost)}
.sg-nav-name{font-size:13px;font-weight:500;color:var(--white-dim);text-decoration:none;white-space:nowrap;transition:color .3s;margin-right:2px}
.sg-nav-name:hover{color:var(--teal-300)}

/* Mobile menu toggle (hamburger) */
.sg-menu-toggle{display:none;background:none;border:none;cursor:pointer;color:var(--white);width:38px;height:38px;align-items:center;justify-content:center;border-radius:50%;transition:all .3s;position:relative;flex-direction:column;gap:5px}
.sg-menu-toggle:hover{background:var(--white-ghost)}
.sg-menu-toggle span{display:block;width:20px;height:1.5px;background:var(--white);transition:all .3s cubic-bezier(.4,0,.2,1)}
.sg-menu-toggle.open span:nth-child(1){transform:translateY(6.5px) rotate(45deg)}
.sg-menu-toggle.open span:nth-child(2){opacity:0}
.sg-menu-toggle.open span:nth-child(3){transform:translateY(-6.5px) rotate(-45deg)}

/* Mobile off-canvas menu — mirrors the cart drawer's slide-in language */
.sg-mobile-menu{position:fixed;top:0;right:0;bottom:0;width:320px;max-width:86vw;background:var(--dark-800);border-left:1px solid rgba(214,48,140,.15);z-index:2000;transform:translateX(100%);transition:transform .4s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column;overflow-y:auto}
.sg-mobile-menu.open{transform:translateX(0)}
.sg-mobile-menu-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid rgba(214,48,140,.1);flex-shrink:0}
.sg-mobile-menu-title{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;letter-spacing:1px;text-transform:uppercase}
.sg-mobile-menu-title em{color:var(--teal-300);font-style:italic}
.sg-mobile-menu-links{list-style:none;padding:12px 8px;flex:1}
.sg-mobile-menu-links li{border-bottom:1px solid rgba(214,48,140,.06)}
.sg-mobile-menu-links a{display:block;padding:16px 16px;text-decoration:none;color:var(--white-dim);font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:600;letter-spacing:.5px;transition:all .3s;opacity:0;transform:translateX(16px)}
.sg-mobile-menu.open .sg-mobile-menu-links a{opacity:1;transform:translateX(0)}
.sg-mobile-menu-links a:hover,.sg-mobile-menu-links a.active{color:var(--teal-300);padding-left:22px}
.sg-mobile-menu-links a.active::before{content:'✦ ';color:var(--teal-400);font-size:13px}
.sg-mobile-menu-foot{padding:20px 24px;border-top:1px solid rgba(214,48,140,.1);display:flex;gap:10px;flex-shrink:0}
.sg-mobile-menu-foot a{flex:1;display:flex;align-items:center;justify-content:center;gap:8px}

/* Mobile bottom nav bar — thumb-reach quick access, mobile only */
.sg-bottom-nav{display:none;position:fixed;left:0;right:0;bottom:0;z-index:1500;background:rgba(255,255,255,.97);backdrop-filter:blur(20px);border-top:1px solid rgba(214,48,140,.15);padding:6px 4px calc(6px + env(safe-area-inset-bottom));box-shadow:0 -8px 24px rgba(0,0,0,.12)}
.sg-bottom-nav-inner{display:flex;align-items:center;justify-content:space-around}
.sg-bottom-nav-item{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;flex:1;background:none;border:none;cursor:pointer;color:var(--white-faint);text-decoration:none;font-family:'Jost',sans-serif;font-size:10px;font-weight:500;letter-spacing:.5px;text-transform:uppercase;padding:7px 4px;position:relative;transition:color .25s}
.sg-bottom-nav-item .sg-bnav-icon{font-size:19px;line-height:1;transition:transform .25s}
.sg-bottom-nav-item:hover,.sg-bottom-nav-item.active{color:var(--teal-300)}
.sg-bottom-nav-item.active .sg-bnav-icon{transform:translateY(-2px)}
.sg-bottom-nav-item.active::after{content:'';position:absolute;top:0;left:50%;transform:translateX(-50%);width:18px;height:2px;border-radius:2px;background:var(--teal-400)}
.sg-bottom-nav-item .sg-cart-badge{top:2px;right:calc(50% - 20px)}

/* Cart badge */
.sg-cart-badge{position:absolute;top:3px;right:3px;width:16px;height:16px;border-radius:50%;background:var(--teal-500);color:#fff;font-size:10px;font-weight:600;display:flex;align-items:center;justify-content:center;line-height:1}

/* Cart Drawer */
.sg-cart-drawer{position:fixed;top:0;right:0;bottom:0;width:380px;background:var(--dark-800);border-left:1px solid rgba(214,48,140,.15);z-index:2000;transform:translateX(100%);transition:transform .35s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column}
.sg-cart-drawer.open{transform:translateX(0)}
.sg-drawer-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1999;opacity:0;pointer-events:none;transition:opacity .35s}
.sg-drawer-overlay.open{opacity:1;pointer-events:all}
.sg-drawer-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid rgba(214,48,140,.1)}
.sg-drawer-body{flex:1;overflow-y:auto;padding:16px 24px}
.sg-drawer-footer{padding:20px 24px;border-top:1px solid rgba(214,48,140,.1)}
.sg-drawer-item{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid rgba(214,48,140,.06)}
.sg-drawer-close{background:none;border:none;cursor:pointer;color:var(--white-dim);font-size:20px;transition:color .3s}
.sg-drawer-close:hover{color:var(--teal-300)}

/* MAIN */
.sg-main{padding-top:64px;min-height:100vh}

/* FOOTER */
.sg-footer{background:var(--teal-800);border-top:1px solid rgba(255,255,255,.1);padding:56px 60px 0}
.sg-footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1.5fr;gap:48px;padding-bottom:48px;border-bottom:1px solid rgba(255,255,255,.1)}
.sg-footer-brand-name{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:#fff;margin-bottom:10px}
.sg-footer-brand-name span{color:var(--teal-300)}
.sg-footer-tagline{font-size:13px;color:rgba(255,255,255,.6);line-height:1.7;margin-bottom:20px;max-width:280px}
.sg-footer-social{display:flex;gap:8px}
.sg-social-btn{width:34px;height:34px;border-radius:50%;border:1px solid rgba(214,48,140,.35);display:flex;align-items:center;justify-content:center;color:var(--teal-300);font-size:13px;text-decoration:none;transition:all .3s}
.sg-social-btn:hover{border-color:var(--teal-300);background:rgba(214,48,140,.15)}
.sg-footer-heading{font-size:11px;font-weight:600;letter-spacing:2.5px;text-transform:uppercase;color:#fff;margin-bottom:18px}
.sg-footer-links{list-style:none}
.sg-footer-links li{margin-bottom:10px}
.sg-footer-links a{text-decoration:none;font-size:14px;color:rgba(255,255,255,.6);transition:color .3s}
.sg-footer-links a:hover{color:var(--teal-300)}
.sg-footer-bottom{display:flex;align-items:center;justify-content:space-between;padding:18px 0;font-size:12px;color:rgba(255,255,255,.4)}
.sg-newsletter-input-wrap{display:flex;border:1px solid rgba(255,255,255,.2);border-radius:2px;overflow:hidden}
.sg-newsletter-input{flex:1;background:rgba(255,255,255,.08);border:none;color:#fff;font-family:'Jost',sans-serif;font-size:13px;padding:11px 16px;outline:none}
.sg-newsletter-input::placeholder{color:rgba(255,255,255,.4)}
.sg-newsletter-btn{background:var(--teal-500);color:#fff;border:none;cursor:pointer;padding:11px 18px;font-size:14px;transition:background .3s}
.sg-newsletter-btn:hover{background:var(--teal-400)}

/* SCROLLBAR */
::-webkit-scrollbar{width:6px}
::-webkit-scrollbar-track{background:var(--dark-900)}
::-webkit-scrollbar-thumb{background:rgba(214,48,140,.3);border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:rgba(214,48,140,.5)}

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
.sg-btn-outline{display:inline-flex;align-items:center;gap:8px;background:transparent;color:var(--teal-300);font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;text-decoration:none;padding:12px 28px;border-radius:2px;border:1px solid rgba(214,48,140,.35);cursor:pointer;transition:all .3s}
.sg-btn-outline:hover{border-color:var(--teal-300);background:rgba(214,48,140,.07);transform:translateY(-2px)}

/* PRODUCT CARDS */
.sg-product-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:2px}
.sg-product-card{background:var(--dark-800);cursor:pointer;transition:all .3s;position:relative;overflow:hidden;border:1px solid rgba(214,48,140,.06);text-decoration:none;color:inherit;display:block}
.sg-product-card:hover{border-color:rgba(214,48,140,.22);transform:translateY(-3px);box-shadow:0 12px 40px rgba(0,0,0,.5)}
.sg-product-img{position:relative;aspect-ratio:1;overflow:hidden;background:var(--dark-750)}
.sg-product-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s}
.sg-product-img-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:radial-gradient(circle at 40% 50%,rgba(214,48,140,.18),var(--dark-750))}
.sg-gem-hex{width:70px;height:70px;clip-path:polygon(50% 0%,93% 25%,93% 75%,50% 100%,7% 75%,7% 25%);background:linear-gradient(135deg,var(--teal-300),var(--teal-700));filter:drop-shadow(0 0 16px rgba(214,48,140,.6));transition:all .4s}
.sg-product-card:hover .sg-product-img img{transform:scale(1.06)}
.sg-product-card:hover .sg-gem-hex{transform:rotate(15deg) scale(1.15);filter:drop-shadow(0 0 28px rgba(214,48,140,.85))}
.sg-product-badge{position:absolute;top:10px;left:10px;font-size:10px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;padding:4px 9px;border-radius:2px}
.sg-badge-gia{background:rgba(214,48,140,.12);color:var(--teal-300);border:1px solid rgba(214,48,140,.28)}
.sg-badge-rare{background:rgba(201,168,76,.12);color:var(--gold-light);border:1px solid rgba(201,168,76,.28)}
.sg-badge-new{background:rgba(80,200,130,.12);color:#7ec87e;border:1px solid rgba(80,200,130,.25)}
.sg-badge-hot{background:rgba(220,80,80,.12);color:#e07070;border:1px solid rgba(220,80,80,.25)}
.sg-product-body{padding:18px}
.sg-product-name{font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:600;color:var(--white);margin-bottom:5px;line-height:1.3}
.sg-product-meta{font-size:12px;color:var(--white-faint);margin-bottom:14px}
.sg-product-meta span{color:rgba(214,48,140,.55);margin:0 5px}
.sg-product-footer{display:flex;align-items:center;justify-content:space-between}
.sg-product-price{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:700;color:var(--teal-300)}
.sg-btn-add{background:var(--teal-600);color:#fff;border:none;cursor:pointer;font-size:11px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;padding:7px 14px;border-radius:2px;transition:all .3s;text-decoration:none;display:inline-block}
.sg-btn-add:hover{background:var(--teal-500);color:#fff}

/* REVEAL ANIMATION */
.sg-reveal{opacity:0;transform:translateY(28px);transition:opacity .65s ease,transform .65s ease}
.sg-reveal.visible{opacity:1;transform:translateY(0)}

/* DIVIDER */
.sg-divider{height:1px;background:linear-gradient(90deg,transparent,rgba(214,48,140,.18),transparent);margin:0 60px}

/* Stagger the mobile menu link reveal for a bit of polish */
.sg-mobile-menu-links li:nth-child(1) a{transition-delay:.05s}
.sg-mobile-menu-links li:nth-child(2) a{transition-delay:.1s}
.sg-mobile-menu-links li:nth-child(3) a{transition-delay:.15s}
.sg-mobile-menu-links li:nth-child(4) a{transition-delay:.2s}
.sg-mobile-menu-links li:nth-child(5) a{transition-delay:.25s}
.sg-mobile-menu-links li:nth-child(6) a{transition-delay:.3s}
.sg-mobile-menu-links li:nth-child(7) a{transition-delay:.35s}

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
  .sg-nav-name{display:none}
  .sg-menu-toggle{display:flex}
  .sg-bottom-nav{display:block}
  .sg-main{padding-bottom:64px}
  .sg-footer{padding-bottom:64px}
  .sg-footer-grid{grid-template-columns:1fr;gap:36px;text-align:left}
  .sg-footer-bottom{flex-direction:column;gap:8px;text-align:center}
  .sg-section{padding:56px 0}
  .sg-section-title{font-size:32px}
}
@media(max-width:480px){
  .sg-nav{padding:0 16px}
  .sg-container{padding:0 16px}
  .sg-divider{margin:0 16px}
  .sg-product-grid{grid-template-columns:repeat(2,1fr);gap:1px}
  .sg-logo-text{font-size:16px;letter-spacing:1px}
  .sg-mobile-menu{width:100%;max-width:100%}
}
</style>
@stack('head_styles')
</head>
<body>

{{-- NAVBAR --}}
<nav class="sg-nav" id="sgNav">
  <a class="sg-logo" href="{{ route('website.home') }}">
    @if ($settings->logoUrl())
      <img src="{{ $settings->logoUrl() }}" alt="{{ $settings->get('site_name', 'Sukaina Gems') }}" class="sg-logo-img">
    @else
      <div class="sg-logo-icon">SG</div>
    @endif
    <span class="sg-logo-text">{{ $settings->get('site_name', 'Sukaina Gems') }}</span>
  </a>
  <div class="sg-nav-links">
    <a href="{{ route('website.home') }}"        class="{{ request()->routeIs('website.home')        ? 'active' : '' }}">Home</a>
    <a href="{{ route('website.collections') }}" class="{{ request()->routeIs('website.collections') ? 'active' : '' }}">Collections</a>
    <a href="{{ route('website.blog.index') }}" class="{{ request()->routeIs('website.blog.*') ? 'active' : '' }}">Events</a>
    <a href="{{ route('website.pages.show', \App\Models\Page::SLUG_ABOUT_US) }}">About</a>
    <a href="{{ route('website.contact') }}" class="{{ request()->routeIs('website.contact') ? 'active' : '' }}">Contact</a>
  </div>
  <div class="sg-nav-right">
    <button class="sg-icon-btn" title="Search">🔍</button>
    @if(auth('customer')->check())
      <a href="{{ route('website.account.profile') }}" class="sg-icon-btn" title="My Account" style="text-decoration:none">👤</a>
      <a href="{{ route('website.account.profile') }}" class="sg-nav-name" title="My Account">
        {{ explode(' ', trim(auth('customer')->user()->name))[0] }}
      </a>
    @else
      <a href="{{ route('website.auth.login') }}" class="sg-icon-btn" title="Sign In" style="text-decoration:none">👤</a>
    @endif

    @if($settings->bool('cart_enabled', true))
    {{-- Cart icon with live badge. $cart comes pre-validated (against
         live product state) from the website.layout View::composer in
         AppServiceProvider -- see CartService. --}}
    <button class="sg-icon-btn" id="sgCartBtn" title="Cart" onclick="openCartDrawer()">
      🛒
      <span class="sg-cart-badge" id="sgCartBadge" style="{{ count($cart) > 0 ? '' : 'display:none' }}">{{ count($cart) }}</span>
    </button>
    @endif

    <button class="sg-menu-toggle" id="sgMenuToggle" title="Menu" aria-label="Open menu" onclick="toggleMobileMenu()">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

{{-- MOBILE MENU (off-canvas) --}}
<div class="sg-drawer-overlay" id="sgMenuOverlay" onclick="closeMobileMenu()"></div>
<div class="sg-mobile-menu" id="sgMobileMenu">
  <div class="sg-mobile-menu-header">
    <span class="sg-mobile-menu-title">✦ <em>Menu</em></span>
    <button class="sg-drawer-close" onclick="closeMobileMenu()" aria-label="Close menu">✕</button>
  </div>
  <ul class="sg-mobile-menu-links">
    <li><a href="{{ route('website.home') }}" class="{{ request()->routeIs('website.home') ? 'active' : '' }}">Home</a></li>
    <li><a href="{{ route('website.collections') }}" class="{{ request()->routeIs('website.collections') ? 'active' : '' }}">Collections</a></li>
    <li><a href="{{ route('website.blog.index') }}" class="{{ request()->routeIs('website.blog.*') ? 'active' : '' }}">Events</a></li>
    <li><a href="{{ route('website.pages.show', \App\Models\Page::SLUG_ABOUT_US) }}">About</a></li>
    <li><a href="{{ route('website.contact') }}" class="{{ request()->routeIs('website.contact') ? 'active' : '' }}">Contact</a></li>
  </ul>
  <div class="sg-mobile-menu-foot">
    @if(auth('customer')->check())
      <a href="{{ route('website.account.profile') }}" class="sg-btn-outline">👤 My Account</a>
    @else
      <a href="{{ route('website.auth.login') }}" class="sg-btn-outline">👤 Sign In</a>
    @endif
  </div>
</div>

{{-- CART DRAWER --}}
@if($settings->bool('cart_enabled', true))
<div class="sg-drawer-overlay" id="sgDrawerOverlay" onclick="closeCartDrawer()"></div>
<div class="sg-cart-drawer" id="sgCartDrawer">
  <div class="sg-drawer-header">
    <div style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600">
      Your Cart
      <span id="drawerCount" style="font-family:'Jost',sans-serif;font-size:13px;font-weight:400;color:var(--white-faint);margin-left:8px">({{ count($cart) }})</span>
    </div>
    <button class="sg-drawer-close" onclick="closeCartDrawer()">✕</button>
  </div>

  <div class="sg-drawer-body" id="drawerBody">
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

{{-- MOBILE BOTTOM NAV — thumb-reach quick access, hidden on desktop --}}
<nav class="sg-bottom-nav" aria-label="Mobile quick navigation">
  <div class="sg-bottom-nav-inner">
    <a href="{{ route('website.home') }}" class="sg-bottom-nav-item {{ request()->routeIs('website.home') ? 'active' : '' }}">
      <span class="sg-bnav-icon">⌂</span><span>Home</span>
    </a>
    <a href="{{ route('website.collections') }}" class="sg-bottom-nav-item {{ request()->routeIs('website.collections') ? 'active' : '' }}">
      <span class="sg-bnav-icon">◈</span><span>Shop</span>
    </a>
    @if($settings->bool('cart_enabled', true))
    <button class="sg-bottom-nav-item" onclick="openCartDrawer()" type="button">
      <span class="sg-bnav-icon" style="position:relative;display:inline-block">
        🛒
        <span class="sg-cart-badge" id="sgCartBadgeBottom" style="{{ count($cart) > 0 ? '' : 'display:none' }}">{{ count($cart) }}</span>
      </span>
      <span>Cart</span>
    </button>
    @endif
    @if(auth('customer')->check())
      <a href="{{ route('website.account.profile') }}" class="sg-bottom-nav-item {{ request()->routeIs('website.account.*') ? 'active' : '' }}">
        <span class="sg-bnav-icon">👤</span><span>Account</span>
      </a>
    @else
      <a href="{{ route('website.auth.login') }}" class="sg-bottom-nav-item">
        <span class="sg-bnav-icon">👤</span><span>Sign In</span>
      </a>
    @endif
    <button class="sg-bottom-nav-item" onclick="toggleMobileMenu()" type="button">
      <span class="sg-bnav-icon">☰</span><span>Menu</span>
    </button>
  </div>
</nav>

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
        <li><a href="{{ route('website.blog.index') }}">Events</a></li>
      </ul>
    </div>
    <div>
      <div class="sg-footer-heading">Connect</div>
      <ul class="sg-footer-links">
        <li><a href="{{ route('website.pages.show', \App\Models\Page::SLUG_ABOUT_US) }}">About Us</a></li>
        <li><a href="{{ route('website.contact') }}">Contact Us</a></li>
        @if($settings->get('contact_email'))<li><a href="mailto:{{ $settings->get('contact_email') }}">Email Us</a></li>@endif
        @if($settings->get('contact_whatsapp'))<li><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings->get('contact_whatsapp')) }}">WhatsApp</a></li>@endif
        <li><a href="{{ route('website.pages.show', \App\Models\Page::SLUG_TERMS_CONDITIONS) }}">Terms &amp; Conditions</a></li>
      </ul>
    </div>
    <div>
      <div class="sg-footer-heading">Newsletter</div>
      <p style="font-size:13px;color:rgba(255,255,255,.6);margin-bottom:14px;line-height:1.6;">New arrivals, rare finds, trade fair dates.</p>
      <div class="sg-newsletter-input-wrap">
        <input class="sg-newsletter-input" type="email" placeholder="Email address">
        <button class="sg-newsletter-btn">→</button>
      </div>
    </div>
  </div>
  <div class="sg-footer-bottom">
    <span>© {{ date('Y') }} {{ $settings->get('site_name', 'Sukaina Gems') }}. All rights reserved.</span>
    <span><a href="{{ route('website.pages.show', \App\Models\Page::SLUG_TERMS_CONDITIONS) }}" style="color:inherit;text-decoration:none">Terms &amp; Conditions</a></span>
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

// ── Mobile off-canvas menu ──────────────────────────────────────────
function toggleMobileMenu() {
  var isOpen = document.getElementById('sgMobileMenu').classList.contains('open');
  if (isOpen) { closeMobileMenu(); } else { openMobileMenu(); }
}
function openMobileMenu() {
  document.getElementById('sgMobileMenu').classList.add('open');
  document.getElementById('sgMenuOverlay').classList.add('open');
  document.getElementById('sgMenuToggle').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeMobileMenu() {
  document.getElementById('sgMobileMenu').classList.remove('open');
  document.getElementById('sgMenuOverlay').classList.remove('open');
  document.getElementById('sgMenuToggle').classList.remove('open');
  document.body.style.overflow = '';
}
// Close the menu automatically when a link is tapped, and if the
// viewport is resized back up to desktop while it's open.
document.querySelectorAll('.sg-mobile-menu-links a').forEach(function (a) {
  a.addEventListener('click', closeMobileMenu);
});
window.addEventListener('resize', function () {
  if (window.innerWidth > 768) closeMobileMenu();
});

function updateCartBadge(count) {
  var badge  = document.getElementById('sgCartBadge');
  var badge2 = document.getElementById('sgCartBadgeBottom');
  var dcnt   = document.getElementById('drawerCount');
  if (badge)  { badge.textContent = count; badge.style.display = count > 0 ? '' : 'none'; }
  if (badge2) { badge2.textContent = count; badge2.style.display = count > 0 ? '' : 'none'; }
  if (dcnt)   { dcnt.textContent = '(' + count + ')'; }
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
