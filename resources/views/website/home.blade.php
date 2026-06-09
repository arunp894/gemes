@extends('website.layout')

@section('title', 'Sukaina Gems')
@section('meta_desc', 'Specialists in Paraiba Tourmaline and Tanzanite. Rare & precious gems curated for collectors worldwide.')

@section('content')

{{-- ════════════════════════════════════════
    HERO
════════════════════════════════════════ --}}
<section style="position:relative;min-height:100vh;display:flex;align-items:center;overflow:hidden">
  {{-- Background --}}
  <div style="position:absolute;inset:0;background:linear-gradient(135deg,#040e0d 0%,#071a14 45%,#0a2020 75%,#050f0c 100%)"></div>

  {{-- Grid lines --}}
  <div style="position:absolute;inset:0;background-image:linear-gradient(rgba(0,191,176,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(0,191,176,.035) 1px,transparent 1px);background-size:80px 80px"></div>

  {{-- Active hero banner background if exists --}}
  @if($heroBanners->isNotEmpty() && $heroBanners->first()->image_url)
  <div style="position:absolute;inset:0;background:url('{{ $heroBanners->first()->image_url }}') center/cover no-repeat;opacity:.18"></div>
  @endif

  {{-- Animated glow orbs --}}
  <div id="heroOrbs" style="position:absolute;inset:0;pointer-events:none">
    <div class="sg-orb" style="width:500px;height:500px;right:4%;top:50%;transform:translateY(-50%);border-radius:50%;background:radial-gradient(circle at 35% 40%,rgba(0,191,176,.16),transparent 70%)"></div>
    <div class="sg-orb" style="width:360px;height:360px;right:8%;top:50%;transform:translateY(-50%);border:1px solid rgba(0,191,176,.12);border-radius:50%"></div>
    <div class="sg-orb" style="width:220px;height:220px;right:14%;top:50%;transform:translateY(-50%);border:1px solid rgba(0,191,176,.22);border-radius:50%"></div>
  </div>

  {{-- Animated hex gem --}}
  <div style="position:absolute;right:10%;top:50%;transform:translateY(-50%);width:200px;height:200px;pointer-events:none">
    <div id="heroGem" style="width:200px;height:200px;clip-path:polygon(50% 0%,93% 25%,93% 75%,50% 100%,7% 75%,7% 25%);background:linear-gradient(135deg,rgba(0,220,200,.88),rgba(0,150,140,.7));box-shadow:0 0 80px rgba(0,191,176,.6),inset 0 0 60px rgba(255,255,255,.1);animation:sg-spin 14s linear infinite"></div>
  </div>
  <div style="position:absolute;right:12%;top:50%;transform:translateY(-50%);width:110px;height:110px;pointer-events:none;margin-top:-45px;margin-right:-45px">
    <div style="width:110px;height:110px;clip-path:polygon(50% 0%,93% 25%,93% 75%,50% 100%,7% 75%,7% 25%);background:linear-gradient(135deg,rgba(0,240,220,.7),rgba(0,191,176,.5));animation:sg-spin-rev 9s linear infinite"></div>
  </div>

  {{-- Content --}}
  <div style="position:relative;z-index:2;padding:120px 60px 80px;max-width:680px">
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
    <div style="display:flex;gap:48px;padding-top:28px;border-top:1px solid rgba(0,191,176,.12)">
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

{{-- ════════════════════════════════════════
    MARQUEE STRIP
════════════════════════════════════════ --}}
<div style="background:var(--teal-700);padding:12px 0;overflow:hidden;border-top:1px solid rgba(0,191,176,.2);border-bottom:1px solid rgba(0,191,176,.2)">
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
<div style="display:grid;grid-template-columns:repeat(3,1fr);background:var(--dark-800);border-bottom:1px solid rgba(0,191,176,.07)" class="sg-reveal">
  @foreach([
    ['icon'=>'♡','title'=>'Crafted With Care','desc'=>'Every gem tells a story, we are here to share yours with the world.'],
    ['icon'=>'✦','title'=>'Beauty With Integrity','desc'=>'Ethically sourced gemstones, crafted with care for people and planet.'],
    ['icon'=>'☆','title'=>'Built On Trust','desc'=>'We hand-select every stone to ensure authenticity, elegance, and meaning.'],
  ] as $v)
  <div style="padding:48px 40px;display:flex;flex-direction:column;align-items:center;text-align:center;gap:14px;border-right:1px solid rgba(0,191,176,.07);transition:background .3s" onmouseenter="this.style.background='rgba(0,191,176,.03)'" onmouseleave="this.style.background=''">
    <div style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;font-size:22px;border-radius:50%;border:1px solid rgba(0,191,176,.22);color:var(--teal-300)">{{ $v['icon'] }}</div>
    <div style="font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;text-transform:uppercase;letter-spacing:2px">{{ $v['title'] }}</div>
    <div style="font-size:14px;line-height:1.8;color:var(--white-faint);max-width:260px">{{ $v['desc'] }}</div>
  </div>
  @endforeach
</div>

{{-- ════════════════════════════════════════
    COLLECTIONS STRIP (from DB categories)
════════════════════════════════════════ --}}
@if($categories->isNotEmpty())
<section style="padding:80px 60px;background:var(--dark-900)">
  <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:40px" class="sg-reveal">
    <div>
      <div class="sg-eyebrow">Browse by Stone</div>
      <h2 class="sg-section-title">Our <em>Collections</em></h2>
    </div>
    <a href="{{ route('website.collections') }}" class="sg-btn-outline">View All</a>
  </div>
  <div style="display:grid;grid-template-columns:repeat({{ min($categories->count(), 5) }},1fr);gap:16px" class="sg-reveal">
    @foreach($categories as $cat)
    @php
      $gemColors = ['#00c4c0','#7080e0','#50c87a','#c9a84c','#e07070','#e08050'];
      $gc = $gemColors[$loop->index % count($gemColors)];
    @endphp
    <a href="{{ route('website.collections', ['category' => strtolower($cat->code)]) }}"
       style="position:relative;overflow:hidden;border-radius:4px;cursor:pointer;border:1px solid rgba(0,191,176,.1);transition:all .4s;aspect-ratio:3/4;display:flex;flex-direction:column;justify-content:flex-end;text-decoration:none;background:radial-gradient(circle at 40% 40%,{{ $gc }}44,rgba(4,14,13,.95))"
       onmouseenter="this.style.borderColor='rgba(0,191,176,.4)';this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 32px rgba(0,0,0,.4)'"
       onmouseleave="this.style.borderColor='rgba(0,191,176,.1)';this.style.transform='';this.style.boxShadow=''">
      <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(4,14,13,.92) 0%,rgba(4,14,13,.25) 50%,transparent 100%)"></div>
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
<section style="padding:80px 60px;background:var(--dark-850)">
  <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:40px" class="sg-reveal">
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
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:2px;margin-top:2px">
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
<section style="padding:80px 60px;background:var(--dark-900)">
  <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:40px" class="sg-reveal">
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
<div style="background:linear-gradient(90deg,var(--dark-750),var(--dark-700),var(--dark-750));border-top:1px solid rgba(0,191,176,.14);border-bottom:1px solid rgba(0,191,176,.14)" class="sg-reveal">
  <div style="display:grid;grid-template-columns:repeat(4,1fr);padding:0 60px">
    @foreach([
      ['icon'=>'🏆','title'=>'GIA Certified','desc'=>'Full gem lab certification on all stones'],
      ['icon'=>'🌿','title'=>'Ethically Sourced','desc'=>'Conflict-free, responsibly mined'],
      ['icon'=>'📦','title'=>'Secure Delivery','desc'=>'Fully insured worldwide shipping'],
      ['icon'=>'💎','title'=>'Expert Appraisal','desc'=>'5+ years of gemological expertise'],
    ] as $t)
    <div style="display:flex;align-items:center;gap:18px;padding:32px 28px;border-right:1px solid rgba(0,191,176,.08);transition:background .3s" onmouseenter="this.style.background='rgba(0,191,176,.03)'" onmouseleave="this.style.background=''">
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
<section style="padding:72px 60px;background:linear-gradient(135deg,var(--dark-750),var(--dark-800));border-bottom:1px solid rgba(0,191,176,.08)" class="sg-reveal">
  <div style="max-width:640px;margin:0 auto;text-align:center">
    <div class="sg-eyebrow" style="text-align:center;margin-bottom:10px">Stay Updated</div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:44px;font-weight:600;margin-bottom:10px">Newsletter</h2>
    <p style="font-size:15px;color:var(--white-dim);margin-bottom:28px">New arrivals, rare finds, trade fair dates — delivered to your inbox.</p>
    <div style="display:flex;max-width:420px;margin:0 auto;border:1px solid rgba(0,191,176,.28);border-radius:2px;overflow:hidden">
      <input style="flex:1;background:rgba(7,20,16,.8);border:none;color:#f0faf8;font-family:'Jost',sans-serif;font-size:14px;padding:14px 18px;outline:none" type="email" placeholder="your@email.com">
      <button style="background:var(--teal-500);color:#fff;border:none;cursor:pointer;font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:2px;text-transform:uppercase;padding:14px 22px;transition:background .3s" onmouseenter="this.style.background='var(--teal-400)'" onmouseleave="this.style.background='var(--teal-500)'">→</button>
    </div>
  </div>
</section>

@endsection

@push('head_styles')
<style>
.sg-hero-badge{display:inline-flex;align-items:center;gap:8px;border:1px solid rgba(0,191,176,.28);background:rgba(0,191,176,.05);padding:6px 16px;border-radius:20px;font-size:11px;font-weight:500;letter-spacing:2px;text-transform:uppercase;color:var(--teal-300);margin-bottom:24px;animation:sg-fade-up .8s ease .2s both}
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
</style>
@endpush
