@extends('website.layout')

@section('title', 'Sukaina Gems')
@section('meta_desc', 'Specialists in Paraiba Tourmaline and Tanzanite. Rare & precious gems curated for collectors worldwide.')

@section('content')

{{-- ════════════════════════════════════════
    HERO
════════════════════════════════════════ --}}
@if($heroBanners->isNotEmpty())
<section class="sg-hero-section sg-hero-slideshow" style="position:relative;overflow:hidden">
  @foreach($heroBanners as $banner)
    @if($banner->link_url)
    <a href="{{ $banner->link_url }}" class="sg-hero-slide {{ $loop->first ? 'active' : '' }}" style="background-image:url('{{ $banner->image_url }}')"></a>
    @else
    <div class="sg-hero-slide {{ $loop->first ? 'active' : '' }}" style="background-image:url('{{ $banner->image_url }}')"></div>
    @endif
  @endforeach

  @if($heroBanners->count() > 1)
  <button type="button" class="sg-hero-arrow prev" onclick="sgHeroPrev()" aria-label="Previous slide">‹</button>
  <button type="button" class="sg-hero-arrow next" onclick="sgHeroNext()" aria-label="Next slide">›</button>
  <div class="sg-hero-dots">
    @foreach($heroBanners as $banner)
      <button type="button" class="sg-hero-dot {{ $loop->first ? 'active' : '' }}" onclick="sgHeroGoto({{ $loop->index }})" aria-label="Go to slide {{ $loop->index + 1 }}"></button>
    @endforeach
  </div>
  @endif
</section>
@else
<section class="sg-hero-section" style="position:relative;display:flex;align-items:center;overflow:hidden">
  {{-- Background --}}
  <div style="position:absolute;inset:0;background:linear-gradient(135deg,#fdf6fb 0%,#f6ecf8 45%,#eef0fb 75%,#fdf6fb 100%)"></div>

  {{-- Grid lines --}}
  <div style="position:absolute;inset:0;background-image:linear-gradient(rgba(214,48,140,.05) 1px,transparent 1px),linear-gradient(90deg,rgba(214,48,140,.05) 1px,transparent 1px);background-size:80px 80px"></div>

  {{-- Animated glow orbs --}}
  <div id="heroOrbs" class="sg-hero-decor" style="position:absolute;inset:0;pointer-events:none">
    <div class="sg-orb" style="width:500px;height:500px;right:4%;top:50%;transform:translateY(-50%);border-radius:50%;background:radial-gradient(circle at 35% 40%,rgba(214,48,140,.16),transparent 70%)"></div>
    <div class="sg-orb" style="width:360px;height:360px;right:8%;top:50%;transform:translateY(-50%);border:1px solid rgba(214,48,140,.12);border-radius:50%"></div>
    <div class="sg-orb" style="width:220px;height:220px;right:14%;top:50%;transform:translateY(-50%);border:1px solid rgba(214,48,140,.22);border-radius:50%"></div>
  </div>

  {{-- Animated hex gem --}}
  <div class="sg-hero-decor" style="position:absolute;right:10%;top:50%;transform:translateY(-50%);width:200px;height:200px;pointer-events:none">
    <div id="heroGem" style="width:200px;height:200px;clip-path:polygon(50% 0%,93% 25%,93% 75%,50% 100%,7% 75%,7% 25%);background:linear-gradient(135deg,rgba(0,120,190,.85),rgba(70,30,140,.75));box-shadow:0 0 80px rgba(214,48,140,.55),inset 0 0 60px rgba(255,255,255,.1);animation:sg-spin 14s linear infinite"></div>
  </div>
  <div class="sg-hero-decor" style="position:absolute;right:12%;top:50%;transform:translateY(-50%);width:110px;height:110px;pointer-events:none;margin-top:-45px;margin-right:-45px">
    <div style="width:110px;height:110px;clip-path:polygon(50% 0%,93% 25%,93% 75%,50% 100%,7% 75%,7% 25%);background:linear-gradient(135deg,rgba(230,50,130,.75),rgba(70,30,140,.55));animation:sg-spin-rev 9s linear infinite"></div>
  </div>

  {{-- Content --}}
  <div class="sg-hero-content" style="position:relative;z-index:2;max-width:680px">
    <div class="sg-hero-badge">
      <span class="sg-badge-dot"></span>
      Fine Gems &amp; Precious Stones
    </div>

    <h1 class="sg-hero-title">
      SUKAINA<br><span>GEMS</span>
    </h1>

    <p class="sg-hero-sub">
      Specialists in Paraiba Tourmaline and Tanzanite.<br>
      5+ years of precious and semi-precious gems,<br>
      curated for discerning collectors worldwide.
    </p>

    <div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:56px">
      <a href="{{ route('website.collections') }}" class="sg-btn-primary">SHOP ALL →</a>
      <a href="{{ route('website.collections') }}" class="sg-btn-outline">View Collections</a>
    </div>

    {{-- Stats from live DB --}}
    <div class="sg-hero-stats" style="display:flex;padding-top:28px;border-top:1px solid rgba(214,48,140,.12)">
      <div>
        <div style="font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:600;color:var(--teal-300);line-height:1" id="stat-gems">{{ $totalGems }}</div>
        <div style="font-size:11px;color:var(--white-faint);text-transform:uppercase;letter-spacing:1px;margin-top:4px">Live Gems</div>
      </div>
      <div>
        <div style="font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:600;color:var(--teal-300);line-height:1">5+</div>
        <div style="font-size:11px;color:var(--white-faint);text-transform:uppercase;letter-spacing:1px;margin-top:4px">Years Expertise</div>
      </div>
      <div>
        <div style="font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:600;color:var(--teal-300);line-height:1">GIA</div>
        <div style="font-size:11px;color:var(--white-faint);text-transform:uppercase;letter-spacing:1px;margin-top:4px">Certified</div>
      </div>
    </div>
  </div>
</section>
@endif

{{-- ════════════════════════════════════════
    MARQUEE STRIP
════════════════════════════════════════ --}}
<div style="background:var(--teal-700);padding:12px 0;overflow:hidden;border-top:1px solid rgba(214,48,140,.2);border-bottom:1px solid rgba(214,48,140,.2)">
  <div class="sg-marquee-track">
    @php $items = ['Paraiba Tourmaline','Blue Tanzanite','GIA Certified','Natural Zircon','Unheated Gems','Free Insured Shipping','Ethically Sourced','Fine Quality Gems']; @endphp
    @foreach(array_merge($items,$items) as $item)
    <span style="display:inline-flex;align-items:center;gap:10px;padding:0 36px;white-space:nowrap;font-size:11px;font-weight:500;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.9)">
      <span style="width:4px;height:4px;border-radius:50%;background:rgba(255,255,255,.45)"></span>{{ $item }}
    </span>
    @endforeach
  </div>
</div>

{{-- ════════════════════════════════════════
    VALUES
════════════════════════════════════════ --}}
<div class="sg-reveal sg-values-grid" style="background:var(--dark-800);border-bottom:1px solid rgba(214,48,140,.07)">
  @foreach([
    ['icon'=>'♡','title'=>'Crafted With Care','desc'=>'Every gem tells a story, we are here to share yours with the world.'],
    ['icon'=>'✦','title'=>'Beauty With Integrity','desc'=>'Ethically sourced gemstones, crafted with care for people and planet.'],
    ['icon'=>'☆','title'=>'Built On Trust','desc'=>'We hand-select every stone to ensure authenticity, elegance, and meaning.'],
  ] as $v)
  <div class="sg-value-item" style="display:flex;flex-direction:column;align-items:center;text-align:center;gap:14px;transition:background .3s" onmouseenter="this.style.background='rgba(214,48,140,.03)'" onmouseleave="this.style.background=''">
    <div style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;font-size:22px;border-radius:50%;border:1px solid rgba(214,48,140,.22);color:var(--teal-300)">{{ $v['icon'] }}</div>
    <div style="font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;text-transform:uppercase;letter-spacing:2px">{{ $v['title'] }}</div>
    <div style="font-size:14px;line-height:1.8;color:var(--white-faint);max-width:260px">{{ $v['desc'] }}</div>
  </div>
  @endforeach
</div>

{{-- ════════════════════════════════════════
    COLLECTIONS STRIP (from DB categories)
════════════════════════════════════════ --}}
@if($categories->isNotEmpty())
<section class="sg-sec-px" style="padding:80px 0;background:var(--dark-900)">
  <div class="sg-reveal sg-sec-header" style="margin-bottom:40px">
    <div>
      <div class="sg-eyebrow">Browse by Stone</div>
      <h2 class="sg-section-title">Our <em>Collections</em></h2>
    </div>
    <a href="{{ route('website.collections') }}" class="sg-btn-outline">View All</a>
  </div>
  <div class="sg-reveal sg-collections-grid" style="display:grid;grid-template-columns:repeat({{ min($categories->count(), 5) }},1fr);gap:16px">
    @foreach($categories as $cat)
    @php
      $gemColors = ['#0078be','#e63282','#7a2f9c','#c9a84c','#fa6e82','#321e8c'];
      $gc = $gemColors[$loop->index % count($gemColors)];
      $tileBg = $cat->image_url
        ? "background-image:url('{$cat->image_url}');background-size:cover;background-position:center"
        : "background:radial-gradient(circle at 40% 40%,{$gc}44,rgba(10,7,22,.95))";
    @endphp
    <a href="{{ route('website.collections', ['category' => strtolower($cat->code)]) }}"
       style="position:relative;overflow:hidden;border-radius:4px;cursor:pointer;border:1px solid rgba(214,48,140,.1);transition:all .4s;aspect-ratio:3/4;display:flex;flex-direction:column;justify-content:flex-end;text-decoration:none;{{ $tileBg }}"
       onmouseenter="this.style.borderColor='rgba(214,48,140,.4)';this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 32px rgba(0,0,0,.4)'"
       onmouseleave="this.style.borderColor='rgba(214,48,140,.1)';this.style.transform='';this.style.boxShadow=''">
      <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(10,7,22,.92) 0%,rgba(10,7,22,.25) 50%,transparent 100%)"></div>
      <div style="position:relative;z-index:2;padding:18px">
        <div style="width:10px;height:10px;border-radius:50%;background:{{ $gc }};box-shadow:0 0 10px {{ $gc }};margin-bottom:8px"></div>
        <div style="font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:600;color:#f0faf8;margin-bottom:3px">{{ $cat->name }}</div>
        <div style="font-size:12px;color:rgba(240,250,248,.4);letter-spacing:1px">{{ $cat->products_count }} gems</div>
      </div>
    </a>
    @endforeach
  </div>
</section>
@endif

{{-- ════════════════════════════════════════
    FEATURED PRODUCTS (from DB)
════════════════════════════════════════ --}}
@if($featuredProducts->isNotEmpty())
<section class="sg-sec-px" style="padding:80px 0;background:var(--dark-850)">
  <div class="sg-reveal sg-sec-header" style="margin-bottom:40px">
    <div>
      <div class="sg-eyebrow">Hand-Picked</div>
      <h2 class="sg-section-title">Featured <em>Gems</em></h2>
    </div>
    <a href="{{ route('website.collections') }}" class="sg-btn-outline">Shop All Gems</a>
  </div>

  <div class="sg-product-grid sg-reveal">
    @foreach($featuredProducts->take(4) as $product)
    @include('website._product_card', ['product' => $product])
    @endforeach
  </div>

  @if($featuredProducts->count() > 4)
  <div class="sg-featured-more-grid">
    @foreach($featuredProducts->skip(4)->take(3) as $product)
    @include('website._product_card', ['product' => $product])
    @endforeach
  </div>
  @endif
</section>
@endif

{{-- ════════════════════════════════════════
    LATEST ARRIVALS (from DB)
════════════════════════════════════════ --}}
@if($latestProducts->isNotEmpty())
<section class="sg-sec-px" style="padding:80px 0;background:var(--dark-900)">
  <div class="sg-reveal sg-sec-header" style="margin-bottom:40px">
    <div>
      <div class="sg-eyebrow">Just In</div>
      <h2 class="sg-section-title">Latest <em>Arrivals</em></h2>
    </div>
    <a href="{{ route('website.collections', ['sort' => 'latest']) }}" class="sg-btn-outline">View All New</a>
  </div>
  <div class="sg-product-grid sg-reveal">
    @foreach($latestProducts as $product)
    @include('website._product_card', ['product' => $product, 'badge' => 'new', 'badgeText' => 'New Arrival'])
    @endforeach
  </div>
</section>
@endif

{{-- ════════════════════════════════════════
    TRUST BADGES
════════════════════════════════════════ --}}
<div style="background:linear-gradient(90deg,var(--dark-750),var(--dark-700),var(--dark-750));border-top:1px solid rgba(214,48,140,.14);border-bottom:1px solid rgba(214,48,140,.14)" class="sg-reveal">
  <div class="sg-trust-grid">
    @foreach([
      ['icon'=>'🏆','title'=>'GIA Certified','desc'=>'Full gem lab certification on all stones'],
      ['icon'=>'🌿','title'=>'Ethically Sourced','desc'=>'Conflict-free, responsibly mined'],
      ['icon'=>'📦','title'=>'Secure Delivery','desc'=>'Fully insured worldwide shipping'],
      ['icon'=>'💎','title'=>'Expert Appraisal','desc'=>'5+ years of gemological expertise'],
    ] as $t)
    <div class="sg-trust-item" style="display:flex;align-items:center;gap:18px;transition:background .3s" onmouseenter="this.style.background='rgba(214,48,140,.03)'" onmouseleave="this.style.background=''">
      <div style="font-size:26px;flex-shrink:0">{{ $t['icon'] }}</div>
      <div>
        <div style="font-size:15px;font-weight:500;margin-bottom:3px">{{ $t['title'] }}</div>
        <div style="font-size:12px;color:var(--white-faint);line-height:1.5">{{ $t['desc'] }}</div>
      </div>
    </div>
    @endforeach
  </div>
</div>

{{-- ════════════════════════════════════════
    NEWSLETTER
════════════════════════════════════════ --}}
<section class="sg-reveal sg-sec-px" style="padding:72px 0;background:linear-gradient(135deg,var(--dark-750),var(--dark-800));border-bottom:1px solid rgba(214,48,140,.08)">
  <div style="max-width:640px;margin:0 auto;text-align:center">
    <div class="sg-eyebrow" style="text-align:center;margin-bottom:10px">Stay Updated</div>
    <h2 class="sg-section-title" style="margin-bottom:10px">Newsletter</h2>
    <p style="font-size:15px;color:var(--white-dim);margin-bottom:28px">New arrivals, rare finds, trade fair dates — delivered to your inbox.</p>
    <div style="display:flex;max-width:420px;margin:0 auto;border:1px solid rgba(214,48,140,.28);border-radius:2px;overflow:hidden">
      <input style="flex:1;background:rgba(36,23,53,.05);border:none;color:#241735;font-family:'Jost',sans-serif;font-size:14px;padding:14px 18px;outline:none" type="email" placeholder="your@email.com">
      <button style="background:var(--teal-500);color:#fff;border:none;cursor:pointer;font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:2px;text-transform:uppercase;padding:14px 22px;transition:background .3s" onmouseenter="this.style.background='var(--teal-400)'" onmouseleave="this.style.background='var(--teal-500)'">→</button>
    </div>
  </div>
</section>

@endsection

@push('head_styles')
<style>
.sg-hero-badge{display:inline-flex;align-items:center;gap:8px;border:1px solid rgba(214,48,140,.28);background:rgba(214,48,140,.05);padding:6px 16px;border-radius:20px;font-size:11px;font-weight:500;letter-spacing:2px;text-transform:uppercase;color:var(--teal-300);margin-bottom:24px;animation:sg-fade-up .8s ease .2s both}
.sg-badge-dot{width:6px;height:6px;border-radius:50%;background:var(--teal-400);animation:sg-blink 2s ease infinite;flex-shrink:0}
.sg-hero-title{font-family:'Cormorant Garamond',serif;font-size:80px;line-height:1;font-weight:700;margin-bottom:22px;animation:sg-fade-up .8s ease .4s both}
.sg-hero-title span{color:var(--teal-300);font-style:italic}
.sg-hero-sub{font-size:16px;line-height:1.8;color:var(--white-dim);margin-bottom:36px;animation:sg-fade-up .8s ease .6s both}
.sg-marquee-track{display:flex;width:max-content;animation:sg-marquee 26s linear infinite}
@keyframes sg-fade-up{from{opacity:0;transform:translateY(22px)}to{opacity:1;transform:translateY(0)}}
@keyframes sg-blink{0%,100%{opacity:1}50%{opacity:.3}}
@keyframes sg-marquee{from{transform:translateX(0)}to{transform:translateX(-50%)}}
@keyframes sg-spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
@keyframes sg-spin-rev{from{transform:rotate(0deg)}to{transform:rotate(-360deg)}}
.sg-orb{position:absolute}

/* ── Hero banner slideshow ─────────────────────────────────────── */
.sg-hero-slideshow{background:var(--dark-950)}
.sg-hero-section.sg-hero-slideshow{min-height:55vh}
.sg-hero-slide{position:absolute;inset:0;display:block;background-position:center;background-size:cover;background-repeat:no-repeat;opacity:0;transition:opacity 1s ease;text-decoration:none}
.sg-hero-slide.active{opacity:1;z-index:1}
.sg-hero-arrow{position:absolute;top:50%;transform:translateY(-50%);z-index:3;width:44px;height:44px;border-radius:50%;border:none;background:rgba(255,255,255,.75);backdrop-filter:blur(6px);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:20px;line-height:1;color:#241735;transition:all .3s}
.sg-hero-arrow:hover{background:#fff;transform:translateY(-50%) scale(1.08)}
.sg-hero-arrow.prev{left:24px}
.sg-hero-arrow.next{right:24px}
.sg-hero-dots{position:absolute;bottom:28px;left:50%;transform:translateX(-50%);z-index:3;display:flex;gap:8px}
.sg-hero-dot{width:8px;height:8px;padding:0;border-radius:50%;border:none;cursor:pointer;background:rgba(255,255,255,.55);transition:all .3s}
.sg-hero-dot.active{background:#fff;width:22px;border-radius:4px}

/* ── Mobile responsiveness ─────────────────────────────────────── */
.sg-sec-px{padding-left:60px;padding-right:60px}
.sg-hero-section{min-height:100vh}
.sg-hero-content{padding:120px 60px 80px}
.sg-hero-stats{gap:48px}
.sg-sec-header{display:flex;align-items:flex-end;justify-content:space-between}
.sg-values-grid{display:grid;grid-template-columns:repeat(3,1fr)}
.sg-value-item{padding:48px 40px;border-right:1px solid rgba(214,48,140,.07)}
.sg-featured-more-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:2px;margin-top:2px}
.sg-trust-grid{display:grid;grid-template-columns:repeat(4,1fr);padding:0 60px}
.sg-trust-item{padding:32px 28px;border-right:1px solid rgba(214,48,140,.08)}

@media(max-width:1024px){
  .sg-sec-px{padding-left:24px;padding-right:24px}
  .sg-trust-grid{padding:0 24px}
}
@media(max-width:768px){
  .sg-hero-section{min-height:auto}
  .sg-hero-section.sg-hero-slideshow{min-height:45vh}
  .sg-hero-arrow{width:36px;height:36px;font-size:16px}
  .sg-hero-decor{display:none}
  .sg-hero-content{padding:96px 24px 56px}
  .sg-hero-title{font-size:52px}
  .sg-values-grid{grid-template-columns:1fr}
  .sg-value-item{border-right:none;border-bottom:1px solid rgba(214,48,140,.07);padding:32px 24px}
  .sg-value-item:last-child{border-bottom:none}
  .sg-collections-grid{grid-template-columns:repeat(2,1fr)!important}
  .sg-featured-more-grid{grid-template-columns:repeat(2,1fr)}
  .sg-trust-grid{grid-template-columns:repeat(2,1fr)}
  .sg-trust-item{padding:24px 20px}
  .sg-trust-item:nth-child(2n){border-right:none}
}
@media(max-width:480px){
  .sg-sec-px{padding-left:16px;padding-right:16px}
  .sg-hero-section.sg-hero-slideshow{min-height:38vh}
  .sg-hero-content{padding:88px 16px 48px}
  .sg-hero-title{font-size:38px}
  .sg-hero-stats{gap:22px;flex-wrap:wrap}
  .sg-sec-header{flex-direction:column;align-items:flex-start;gap:14px}
  .sg-collections-grid{grid-template-columns:repeat(2,1fr)!important;gap:10px!important}
  .sg-featured-more-grid{grid-template-columns:1fr}
  .sg-trust-grid{grid-template-columns:1fr;padding:0 16px}
  .sg-trust-item{border-right:none;border-bottom:1px solid rgba(214,48,140,.08)}
  .sg-trust-item:last-child{border-bottom:none}
}
</style>
@endpush

@push('scripts')
<script>
(function () {
  var slides = document.querySelectorAll('.sg-hero-slide');
  var dots   = document.querySelectorAll('.sg-hero-dot');
  if (slides.length < 2) return;

  var idx = 0, timer;

  function show(n) {
    slides[idx].classList.remove('active');
    if (dots[idx]) dots[idx].classList.remove('active');
    idx = (n + slides.length) % slides.length;
    slides[idx].classList.add('active');
    if (dots[idx]) dots[idx].classList.add('active');
  }

  function resetTimer() {
    clearInterval(timer);
    timer = setInterval(function () { show(idx + 1); }, 6000);
  }

  window.sgHeroNext = function () { show(idx + 1); resetTimer(); };
  window.sgHeroPrev = function () { show(idx - 1); resetTimer(); };
  window.sgHeroGoto = function (n) { show(n); resetTimer(); };

  resetTimer();
})();
</script>
@endpush
