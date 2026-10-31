@extends('layouts.app')

@section('content')
@php
  $items = [];
  while (have_posts()) { the_post(); $items[] = \App\Content\Repo::entity(get_post()); }
  wp_reset_postdata();
  $featured = (! is_paged() && $items) ? $items[0] : null;
  $cats = [];
  foreach (\App\Content\Repo::posts() as $x) { $cats[$x['cat']] = true; }
  $cats = array_keys($cats);
@endphp
@include('partials.page-hero')
@if ($featured)
<section class="section section--tight"><div class="container">
  <article class="blog-featured reveal">
    <div class="blog-featured__media"><img src="{{ amol_img($featured, 'blog') }}" width="900" height="506" alt="{{ $featured['title'] }}"></div>
    <div class="blog-featured__body"><span class="chip chip--accent" style="align-self:flex-start">{{ $featured['cat'] }} · مقاله‌ی ویژه</span>
      <h2><a href="{{ amol_entity_url($featured, 'post') }}">{{ $featured['title'] }}</a></h2><p class="muted">{{ $featured['excerpt'] }}</p>
      <div class="post-meta"><span>@icon('calendar-days', 'i--sm') <time datetime="{{ $featured['date'] }}">{{ $featured['jdate'] }}</time></span><span>@icon('clock', 'i--sm') {{ $featured['read'] }} دقیقه مطالعه</span></div></div>
  </article>
</div></section>
@endif
<section class="section" id="posts"><div class="container">
  <x-sec-head eyebrow="همه‌ی مقالات" title="جدیدترین مقالات وبلاگ آمل نوین" :center="false" />
  <div class="toolbar">
    <div class="filters" role="group" aria-label="فیلتر دسته‌بندی مقالات"><button class="filter-btn is-active" type="button" data-filter="all" aria-pressed="true">همه</button>@foreach ($cats as $c)<button class="filter-btn" type="button" data-filter="{{ str_replace(' ', '_', $c) }}" aria-pressed="false">{{ $c }}</button>@endforeach</div>
    <div class="toolbar__search">@icon('search')<input type="search" data-filter-search placeholder="جست‌وجو در مقالات…" aria-label="جست‌وجو در مقالات"></div>
  </div>
  <p class="result-count">نمایش <strong data-filter-count>{{ count($items) }}</strong> مقاله</p>
  <div class="posts" data-filter-group=".posts .post-card">
    @foreach ($items as $p)
    <article class="post-card reveal" data-cat="{{ str_replace(' ', '_', $p['cat']) }}" data-search="{{ $p['title'] }} {{ $p['excerpt'] }}">
      <div class="post-card__media"><img src="{{ amol_img($p, 'blog') }}" width="900" height="506" loading="lazy" decoding="async" alt="{{ $p['title'] }}"></div>
      <div class="post-card__body"><span class="chip chip--accent" style="align-self:flex-start">{{ $p['cat'] }}</span><h3><a href="{{ amol_entity_url($p, 'post') }}">{{ $p['title'] }}</a></h3><p>{{ $p['excerpt'] }}</p>
      <div class="post-meta"><span>@icon('calendar-days', 'i--sm') <time datetime="{{ $p['date'] }}">{{ $p['jdate'] }}</time></span><span>@icon('clock', 'i--sm') {{ $p['read'] }} دقیقه مطالعه</span></div></div>
    </article>
    @endforeach
  </div>
  <div class="empty-state"><p>مقاله‌ای پیدا نشد؛ عبارت دیگری را جست‌وجو کنید.</p></div>
  {!! get_the_posts_pagination(['mid_size' => 1, 'prev_text' => 'قبلی', 'next_text' => 'بعدی']) !!}
</div></section>
@foreach (($page['sections'] ?? []) as $sec){!! amol_section($sec, $loop->iteration) !!}@endforeach
@endsection
