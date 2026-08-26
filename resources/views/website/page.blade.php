@extends('website.layout')

@section('title', $page->meta_title ?: $page->title)
@section('meta_desc', $page->meta_description ?: \Illuminate\Support\Str::limit(trim(strip_tags($page->content)), 160))

@push('head_styles')
<style>
.sg-page-hero{padding:150px 0 40px}
.sg-page-hero .sg-container{max-width:820px}
.sg-page-title{font-family:'Cormorant Garamond',serif;font-size:42px;font-weight:600;color:var(--white)}
.sg-page-body{max-width:820px;margin:0 auto 100px;font-size:16px;line-height:1.85;color:var(--white-dim)}
.sg-page-body p{margin-bottom:1.4em}
.sg-page-body a{color:var(--teal-300)}
.sg-page-body h2,.sg-page-body h3{font-family:'Cormorant Garamond',serif;color:var(--white);margin:1.4em 0 .6em}
.sg-page-body ul,.sg-page-body ol{margin:0 0 1.4em 1.4em}
@media(max-width:768px){.sg-page-title{font-size:30px}.sg-page-hero{padding:120px 0 24px}}
@media(max-width:480px){.sg-page-hero{padding:104px 0 20px}.sg-page-title{font-size:26px}.sg-page-body{font-size:15px}}
</style>
@endpush

@section('content')

<section class="sg-page-hero">
    <div class="sg-container">
        <h1 class="sg-page-title sg-reveal">{{ $page->title }}</h1>
    </div>
</section>

<div class="sg-container">
    <div class="sg-page-body sg-reveal">
        {!! $page->content !!}
    </div>
</div>

@endsection
