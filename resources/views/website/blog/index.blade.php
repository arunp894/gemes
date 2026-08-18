@extends('website.layout')

@section('title', 'Journal')
@section('meta_desc', 'Stories, guides, and news from ' . $settings->get('site_name', 'Sukaina Gems') . ' — gemstone care, sourcing, and the world of Paraiba Tourmaline and Tanzanite.')

@push('head_styles')
<style>
.sg-blog-hero{padding:150px 0 50px}
.sg-blog-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:28px;margin-top:20px}
.sg-blog-card{background:var(--dark-800);border:1px solid rgba(0,191,176,.06);border-radius:2px;overflow:hidden;text-decoration:none;color:inherit;display:block;transition:all .3s}
.sg-blog-card:hover{border-color:rgba(0,191,176,.22);transform:translateY(-3px);box-shadow:0 12px 40px rgba(0,0,0,.5)}
.sg-blog-card-img{aspect-ratio:16/10;background:radial-gradient(circle at 40% 50%,rgba(0,191,176,.18),var(--dark-750));overflow:hidden}
.sg-blog-card-img img{width:100%;height:100%;object-fit:cover}
.sg-blog-card-body{padding:22px}
.sg-blog-date{font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:var(--teal-400);margin-bottom:8px}
.sg-blog-title{font-family:'Cormorant Garamond',serif;font-size:21px;font-weight:600;color:var(--white);line-height:1.3;margin-bottom:10px}
.sg-blog-excerpt{font-size:14px;color:var(--white-faint);line-height:1.6;margin-bottom:0}
.sg-blog-empty{text-align:center;padding:80px 0;color:var(--white-faint)}
.sg-blog-pager{display:flex;justify-content:center;gap:12px;margin-top:56px}
@media(max-width:1024px){.sg-blog-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){.sg-blog-grid{grid-template-columns:1fr}.sg-blog-hero{padding:120px 0 30px}}
</style>
@endpush

@section('content')

<section class="sg-blog-hero">
    <div class="sg-container">
        <div class="sg-eyebrow sg-reveal">The Journal</div>
        <h1 class="sg-section-title sg-reveal">Stories from the <em>world of gems</em></h1>
    </div>
</section>

<section class="sg-section" style="padding-top:0">
    <div class="sg-container">

        @if ($posts->isEmpty())
            <div class="sg-blog-empty sg-reveal">
                <div style="font-size:40px;margin-bottom:14px">💎</div>
                <p>No posts published yet — check back soon.</p>
            </div>
        @else
            <div class="sg-blog-grid">
                @foreach ($posts as $post)
                    <a href="{{ route('website.blog.show', $post) }}" class="sg-blog-card sg-reveal">
                        <div class="sg-blog-card-img">
                            @if ($post->image_thumb_url)
                                <img src="{{ $post->image_medium_url }}" alt="{{ $post->title }}">
                            @else
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">
                                    <div class="sg-gem-hex"></div>
                                </div>
                            @endif
                        </div>
                        <div class="sg-blog-card-body">
                            <div class="sg-blog-date">
                                {{ optional($post->published_at)->format('d M Y') ?? optional($post->created_at)->format('d M Y') }}
                                &middot; {{ $post->readingTimeMinutes() }} min read
                            </div>
                            <h3 class="sg-blog-title">{{ $post->title }}</h3>
                            <p class="sg-blog-excerpt">{{ $post->displayExcerpt(120) }}</p>
                        </div>
                    </a>
                @endforeach
            </div>

            @if ($posts->hasPages())
                <div class="sg-blog-pager">
                    @if ($posts->previousPageUrl())
                        <a href="{{ $posts->previousPageUrl() }}" class="sg-btn-outline">&larr; Newer</a>
                    @endif
                    @if ($posts->nextPageUrl())
                        <a href="{{ $posts->nextPageUrl() }}" class="sg-btn-outline">Older &rarr;</a>
                    @endif
                </div>
            @endif
        @endif

    </div>
</section>

@endsection
