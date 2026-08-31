@extends('website.layout')

@section('title', $blog->meta_title ?: $blog->title)
@section('meta_desc', $blog->meta_description ?: $blog->displayExcerpt(160))

@push('head_styles')
<style>
.sg-post-hero{padding:150px 0 40px}
.sg-post-hero .sg-container{max-width:820px}
.sg-post-meta{font-size:12px;letter-spacing:1px;text-transform:uppercase;color:var(--teal-400);margin-bottom:16px}
.sg-post-title{font-family:'Cormorant Garamond',serif;font-size:42px;font-weight:600;line-height:1.2;color:var(--white)}
.sg-post-image{max-width:820px;margin:0 auto 10px;border-radius:2px;overflow:hidden}
.sg-post-image img{width:100%;display:block}
.sg-post-body{max-width:820px;margin:0 auto;font-size:16px;line-height:1.85;color:var(--white-dim)}
.sg-post-body p{margin-bottom:1.4em}
.sg-post-body a{color:var(--teal-300)}
.sg-post-body h2,.sg-post-body h3{font-family:'Cormorant Garamond',serif;color:var(--white);margin:1.4em 0 .6em;scroll-margin-top:88px}
.sg-post-back{display:inline-block;margin-bottom:24px}
.sg-post-toc{max-width:820px;margin:0 auto 40px;background:var(--dark-800);border:1px solid rgba(0,191,176,.12);border-radius:4px;padding:20px 24px}
.sg-post-toc-title{font-size:11px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--teal-400);margin-bottom:12px}
.sg-post-toc ol{list-style:none;margin:0;padding:0;counter-reset:toc}
.sg-post-toc li{counter-increment:toc;margin-bottom:8px}
.sg-post-toc li:last-child{margin-bottom:0}
.sg-post-toc li::before{content:counter(toc) ".";display:inline-block;width:20px;color:var(--teal-400);font-size:13px}
.sg-post-toc li.sg-toc-level-3{counter-increment:none;padding-left:20px}
.sg-post-toc li.sg-toc-level-3::before{content:"–";width:20px}
.sg-post-toc a{color:var(--white-dim);text-decoration:none;font-size:14px;transition:color .3s}
.sg-post-toc a:hover{color:var(--teal-300)}
.sg-related{max-width:1000px;margin:0 auto}
.sg-related-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:24px}
@media(max-width:768px){.sg-post-title{font-size:30px}.sg-post-hero{padding:120px 0 24px}.sg-related-grid{grid-template-columns:1fr}}
@media(max-width:480px){.sg-post-hero{padding:104px 0 20px}.sg-post-title{font-size:26px}.sg-post-body{font-size:15px}}
</style>
@endpush

@section('content')

<section class="sg-post-hero">
    <div class="sg-container">
        <a href="{{ route('website.blog.index') }}" class="sg-btn-outline sg-post-back">&larr; Journal</a>
        <div class="sg-post-meta sg-reveal">
            {{ optional($blog->published_at)->format('d F Y') ?? optional($blog->created_at)->format('d F Y') }}
            &middot; {{ $blog->readingTimeMinutes() }} min read
        </div>
        <h1 class="sg-post-title sg-reveal">{{ $blog->title }}</h1>
    </div>
</section>

@if ($blog->image_medium_url)
<div class="sg-post-image sg-reveal">
    <img src="{{ $blog->image_medium_url }}" alt="{{ $blog->title }}">
</div>
@endif

<section class="sg-section" style="padding-bottom:0">
    <div class="sg-container">
        @if (count($tableOfContents) > 1)
        <nav class="sg-post-toc sg-reveal" aria-label="Table of contents">
            <div class="sg-post-toc-title">On This Page</div>
            <ol>
                @foreach ($tableOfContents as $item)
                    <li class="sg-toc-level-{{ $item['level'] }}">
                        <a href="#{{ $item['id'] }}">{{ $item['text'] }}</a>
                    </li>
                @endforeach
            </ol>
        </nav>
        @endif

        <div class="sg-post-body sg-reveal">
            {!! $content !!}
        </div>
    </div>
</section>

@if ($relatedPosts->isNotEmpty())
<section class="sg-section" style="padding-top:0">
    <div class="sg-container">
        <div class="sg-divider" style="margin-bottom:48px"></div>
        <div class="sg-related">
            <div class="sg-eyebrow sg-reveal">Keep Reading</div>
            <div class="sg-related-grid">
                @foreach ($relatedPosts as $post)
                    <a href="{{ route('website.blog.show', $post) }}" class="sg-blog-card sg-reveal" style="background:var(--dark-800);border:1px solid rgba(0,191,176,.06);border-radius:2px;overflow:hidden;text-decoration:none;color:inherit;display:block">
                        <div style="aspect-ratio:16/10;background:radial-gradient(circle at 40% 50%,rgba(0,191,176,.18),var(--dark-750));overflow:hidden">
                            @if ($post->image_thumb_url)
                                <img src="{{ $post->image_thumb_url }}" alt="{{ $post->title }}" style="width:100%;height:100%;object-fit:cover">
                            @endif
                        </div>
                        <div style="padding:16px">
                            <h4 style="font-family:'Cormorant Garamond',serif;font-size:17px;font-weight:600;color:var(--white);line-height:1.3;margin:0">{{ $post->title }}</h4>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

@endsection
