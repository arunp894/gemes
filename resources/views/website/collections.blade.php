@extends('website.layout')

@section('title', 'Collections')
@section('meta_desc', 'Browse our full catalogue of rare gemstones — Paraiba Tourmaline, Tanzanite, Zircon and more.')

@section('content')

{{-- HERO BAND --}}
<div class="sg-coll-hero" style="display:flex;flex-direction:column;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--dark-900),var(--dark-750));border-bottom:1px solid rgba(214,48,140,.1);text-align:center;position:relative;overflow:hidden">
  <div style="position:absolute;inset:0;background-image:linear-gradient(rgba(214,48,140,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(214,48,140,.025) 1px,transparent 1px);background-size:60px 60px"></div>
  <div style="position:relative;z-index:1">
    <div class="sg-eyebrow" style="text-align:center;margin-bottom:10px">All Products</div>
    <h1 class="sg-page-hero-title" style="font-family:'Cormorant Garamond',serif;font-weight:700">Our <em style="color:var(--teal-300);font-style:italic">Collection</em></h1>
    <p style="font-size:15px;color:var(--white-dim);margin-top:10px">Rare gems from the world's finest sources</p>
  </div>
</div>

{{-- FILTER BAR --}}
<div class="sg-filter-bar sg-sec-px" style="display:flex;align-items:center;gap:12px;padding:16px 0;background:var(--dark-800);border-bottom:1px solid rgba(214,48,140,.07);flex-wrap:wrap">
  {{-- Category chips --}}
  <a class="sg-chip" href="{{ route('website.collections', array_merge(request()->except('category','page'), ['sort' => $sort])) }}"
     style="border-radius:20px;font-size:12px;font-weight:600;letter-spacing:1px;text-transform:uppercase;cursor:pointer;border:1px solid rgba(214,48,140,.25);text-decoration:none;transition:all .3s;{{ !$categorySlug ? 'background:rgba(214,48,140,.1);border-color:var(--teal-400);color:var(--teal-300)' : 'color:rgba(36,23,53,.35)' }}">All</a>

  @foreach($categories as $cat)
  @php $catCode = strtolower($cat->code); @endphp
  <a class="sg-chip" href="{{ route('website.collections', array_merge(request()->except('category','page'), ['category' => $catCode, 'sort' => $sort])) }}"
     style="border-radius:20px;font-size:12px;font-weight:600;letter-spacing:1px;text-transform:uppercase;cursor:pointer;border:1px solid rgba(214,48,140,.25);text-decoration:none;transition:all .3s;{{ $categorySlug === $catCode ? 'background:rgba(214,48,140,.1);border-color:var(--teal-400);color:var(--teal-300)' : 'color:rgba(36,23,53,.35)' }}">
    {{ $cat->name }}
    <span style="opacity:.5;font-size:10px">({{ $cat->products_count }})</span>
  </a>
  @endforeach

  <div style="width:1px;height:20px;background:rgba(214,48,140,.15);margin:0 4px"></div>

  {{-- Search --}}
  <form method="GET" action="{{ route('website.collections') }}" class="sg-search-form" style="display:flex;gap:0;margin-left:auto">
    @if($categorySlug)<input type="hidden" name="category" value="{{ $categorySlug }}">@endif
    <input name="q" value="{{ $search }}" placeholder="Search gems…" class="sg-search-input"
      style="background:var(--dark-750);border:1px solid rgba(214,48,140,.2);border-right:none;color:#241735;font-family:'Jost',sans-serif;font-size:13px;outline:none;border-radius:2px 0 0 2px">
    <button type="submit" class="sg-search-btn" style="background:var(--teal-600);border:none;color:#fff;cursor:pointer;border-radius:0 2px 2px 0;font-size:13px;transition:background .3s" onmouseenter="this.style.background='var(--teal-500)'" onmouseleave="this.style.background='var(--teal-600)'">🔍</button>
  </form>

  {{-- Sort --}}
  <select onchange="window.location=this.value" class="sg-sort-select"
    style="background:var(--dark-750);border:1px solid rgba(214,48,140,.2);color:#241735;font-family:'Jost',sans-serif;font-size:13px;border-radius:2px;outline:none;cursor:pointer">
    @foreach(['featured'=>'Featured','latest'=>'Newest First','price_asc'=>'Price: Low → High','price_desc'=>'Price: High → Low','carat_desc'=>'Carat: Heaviest'] as $val=>$label)
    <option value="{{ route('website.collections', array_merge(request()->except('sort','page'), ['sort'=>$val])) }}" {{ $sort===$val?'selected':''}}>{{ $label }}</option>
    @endforeach
  </select>
</div>

{{-- PROMO BANNER --}}
@if($promoBanner && $promoBanner->image_url)
<div style="margin:0;overflow:hidden;max-height:180px;position:relative">
  <img src="{{ $promoBanner->image_url }}" alt="{{ $promoBanner->title }}" style="width:100%;height:180px;object-fit:cover;opacity:.5">
  <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;background:rgba(10,7,22,.4);text-align:center">
    <div style="font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:600;color:#f0faf8">{{ $promoBanner->title }}</div>
    @if($promoBanner->subtitle)<div style="font-size:15px;color:var(--white-dim);margin-top:6px">{{ $promoBanner->subtitle }}</div>@endif
    @if($promoBanner->link_url)<a href="{{ $promoBanner->link_url }}" style="margin-top:14px;display:inline-block;padding:10px 24px;background:var(--teal-500);color:#fff;text-decoration:none;font-size:12px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;border-radius:2px;transition:background .3s">{{ $promoBanner->link_text ?? 'Learn More' }}</a>@endif
  </div>
</div>
@endif

{{-- RESULTS COUNT --}}
<div class="sg-sec-px" style="padding:20px 0 0;background:var(--dark-900);font-size:13px;color:var(--white-faint)">
  @if($search)Showing results for "<strong style="color:var(--teal-300)">{{ $search }}</strong>" — @endif
  {{ $products->total() }} gem{{ $products->total() !== 1 ? 's' : '' }} found
</div>

{{-- PRODUCTS GRID --}}
<section class="sg-sec-px" style="padding:20px 0 80px;background:var(--dark-900)">
  @if($products->isEmpty())
  <div style="text-align:center;padding:80px 0">
    <div style="font-size:48px;margin-bottom:16px">💎</div>
    <h3 style="font-family:'Cormorant Garamond',serif;font-size:28px;margin-bottom:8px">No gems found</h3>
    <p style="color:var(--white-faint);margin-bottom:24px">Try a different category or search term</p>
    <a href="{{ route('website.collections') }}" class="sg-btn-primary">Browse All Gems</a>
  </div>
  @else
  <div class="sg-product-grid">
    @foreach($products as $product)
    @include('website._product_card', ['product' => $product])
    @endforeach
  </div>

  {{-- PAGINATION --}}
  @if($products->hasPages())
  <div style="display:flex;justify-content:center;gap:8px;margin-top:48px;flex-wrap:wrap">
    @if($products->onFirstPage())
    <span style="padding:9px 18px;border:1px solid rgba(214,48,140,.12);border-radius:2px;color:var(--white-faint);font-size:13px">← Prev</span>
    @else
    <a href="{{ $products->previousPageUrl() }}" style="padding:9px 18px;border:1px solid rgba(214,48,140,.25);border-radius:2px;color:var(--teal-300);font-size:13px;text-decoration:none;transition:all .3s" onmouseenter="this.style.background='rgba(214,48,140,.08)'" onmouseleave="this.style.background=''">← Prev</a>
    @endif

    @foreach($products->getUrlRange(max(1,$products->currentPage()-2), min($products->lastPage(),$products->currentPage()+2)) as $page => $url)
    @if($page == $products->currentPage())
    <span style="padding:9px 16px;background:var(--teal-600);border:1px solid var(--teal-500);border-radius:2px;color:#fff;font-size:13px">{{ $page }}</span>
    @else
    <a href="{{ $url }}" style="padding:9px 16px;border:1px solid rgba(214,48,140,.2);border-radius:2px;color:var(--white-dim);font-size:13px;text-decoration:none;transition:all .3s" onmouseenter="this.style.background='rgba(214,48,140,.08)'" onmouseleave="this.style.background=''">{{ $page }}</a>
    @endif
    @endforeach

    @if($products->hasMorePages())
    <a href="{{ $products->nextPageUrl() }}" style="padding:9px 18px;border:1px solid rgba(214,48,140,.25);border-radius:2px;color:var(--teal-300);font-size:13px;text-decoration:none;transition:all .3s" onmouseenter="this.style.background='rgba(214,48,140,.08)'" onmouseleave="this.style.background=''">Next →</a>
    @else
    <span style="padding:9px 18px;border:1px solid rgba(214,48,140,.12);border-radius:2px;color:var(--white-faint);font-size:13px">Next →</span>
    @endif
  </div>
  @endif
  @endif
</section>

@endsection

@push('head_styles')
<style>
/* ── Mobile responsiveness ─────────────────────────────────────── */
.sg-sec-px{padding-left:60px;padding-right:60px}
.sg-coll-hero{height:280px}
.sg-page-hero-title{font-size:60px}
.sg-chip{padding:7px 18px}
.sg-search-input{padding:8px 14px;width:200px}
.sg-search-btn{padding:8px 14px}
.sg-sort-select{padding:8px 14px}

@media(max-width:1024px){
  .sg-sec-px{padding-left:24px;padding-right:24px}
}
@media(max-width:768px){
  .sg-coll-hero{height:220px}
  .sg-page-hero-title{font-size:42px}
  .sg-search-form{width:100%;margin-left:0!important;margin-top:8px}
  .sg-search-input{flex:1;width:auto}
  .sg-sort-select{width:100%}
  .sg-chip,.sg-search-input,.sg-search-btn,.sg-sort-select{padding:12px 16px}
}
@media(max-width:480px){
  .sg-sec-px{padding-left:16px;padding-right:16px}
  .sg-coll-hero{height:200px}
  .sg-page-hero-title{font-size:34px}
}
</style>
@endpush
