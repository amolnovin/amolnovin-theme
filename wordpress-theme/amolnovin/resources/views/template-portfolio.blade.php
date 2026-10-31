{{--
  Template Name: نمونه‌کارها (پورتفولیو)
--}}
@extends('layouts.app')

@section('content')
@php
  $projects = \App\Content\Repo::projects();
  $cats = \App\Content\Repo::projectCats();
  $svcs = amol_project_services();
  $f = \App\Content\Repo::projectBy()['maneli-plants'] ?? null;
  $detailCount = count(array_filter($projects, fn ($p) => ! empty($p['detail'])));
@endphp
@include('partials.page-hero')

<section class="section section--tight">
  <div class="container"><div class="stats">
    @foreach (\App\Support\Counters::items('portfolio') as $stat)
    <div class="stat reveal"><span class="stat__num" data-count="{{ $stat['number'] }}" data-suffix="{{ $stat['suffix'] }}">{{ amol_fa($stat['value']) }}{{ $stat['suffix'] }}</span><span class="stat__label">{{ $stat['label'] }}</span></div>
    @endforeach
  </div></div>
</section>

@if ($f)
<section class="section section--tight" aria-label="مطالعه‌ی موردی ویژه">
  <div class="container">
    <article class="feature-case reveal">
      <div class="feature-case__media"><img src="{{ amol_img($f, 'portfolio', '-lg') }}" width="1100" height="720" alt="مطالعه‌ی موردی طراحی سایت و افزونه‌ی اختصاصی {{ $f['title'] }}" loading="lazy"></div>
      <div class="feature-case__body">
        <span class="eyebrow">مطالعه‌ی موردی ویژه</span>
        <h2>{{ $f['title'] }}: فروشگاهی که گیاه مناسب را پیشنهاد می‌دهد</h2>
        <p>{{ $f['overview'] ?? '' }}</p>
        <ul class="chip-row">@foreach ($f['tech'] as $t)<li class="chip">{{ $t }}</li>@endforeach</ul>
        <div class="btn-row"><a class="btn btn--cta" href="{{ amol_entity_url($f, 'project') }}">مطالعه‌ی موردی @icon('arrow-left', 'i--arrow')</a><a class="btn btn--light" href="{{ $f['url'] ?? '#' }}" target="_blank" rel="noopener">بازدید سایت @icon('external-link', 'i--sm')</a></div>
      </div>
    </article>
  </div>
</section>
@endif

<section class="section" id="projects" aria-labelledby="projects-title">
  <div class="container">
    <x-sec-head eyebrow="همه‌ی نمونه‌کارها" title="نمونه کار طراحی سایت، پشتیبانی و سئو در یک نگاه" desc="بر اساس دسته‌بندی یا نوع خدمت فیلتر کنید یا نام پروژه را جست‌وجو کنید. روی هر نمونه بزنید تا مطالعه‌ی موردی را ببینید." :center="false" id="projects-title" />
    <div class="toolbar">
      <div class="filters" role="group" aria-label="فیلتر بر اساس دسته">
        @foreach ($cats as [$k, $l])<button class="filter-btn{{ $loop->first ? ' is-active' : '' }}" type="button" data-filter="{{ $k }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">{{ $l }}</button>@endforeach
        <span style="width:1px;background:var(--line-2);margin-inline:.3rem"></span>
        @foreach ($svcs as $k => $l)<button class="filter-btn" type="button" data-filter="{{ $k }}" aria-pressed="false">{{ $l }}</button>@endforeach
      </div>
      <div class="toolbar__search">@icon('search')<input type="search" data-filter-search placeholder="جست‌وجو در نمونه‌کارها…" aria-label="جست‌وجو در نمونه‌کارها"></div>
    </div>
    <p class="result-count">نمایش <strong data-filter-count>{{ count($projects) }}</strong> نمونه‌کار</p>
    <div class="projects" data-filter-group=".projects .project">
      @foreach ($projects as $p)<x-project-card :p="$p" />@endforeach
    </div>
    <div class="empty-state"><p>نمونه‌کاری با این مشخصات پیدا نشد. فیلترها را تغییر دهید یا <a href="{{ amol_url('/contact-us/') }}">برای پروژه‌ی مشابه با ما تماس بگیرید</a>.</p></div>
  </div>
</section>

<section class="section section--alt">
  <div class="container">
    <x-sec-head eyebrow="روش کار ما" title="هر نمونه‌کار با همین چهار مرحله ساخته شده است" :center="true" />
    <ol class="steps steps--4">
      <li class="step reveal"><span class="step__num">۱</span><h3>تحلیل و استراتژی</h3><p>شناخت کسب‌وکار، مخاطب و رقبا و تعریف هدف سایت.</p></li>
      <li class="step reveal"><span class="step__num">۲</span><h3>طراحی و تأیید</h3><p>نمونه‌ی گرافیکی صفحات و بازبینی با شما تا تأیید نهایی.</p></li>
      <li class="step reveal"><span class="step__num">۳</span><h3>توسعه و سئو</h3><p>پیاده‌سازی با وردپرس یا کدنویسی اختصاصی، سرعت و سئوی فنی.</p></li>
      <li class="step reveal"><span class="step__num">۴</span><h3>پشتیبانی و رشد</h3><p>{{ $site['support_months'] }} ماه پشتیبانی فنی و گزارش‌دهی برای بهبود مستمر.</p></li>
    </ol>
  </div>
</section>

<section class="section section--tight">
  <div class="container">
    <x-sec-head eyebrow="کسب‌وکارهایی که به ما اعتماد کرده‌اند" title="برخی از کارفرمایان آمل نوین" :center="true" />
    <div class="clients">@foreach ($projects as $p)<span class="reveal">{{ $p['title'] }}</span>@endforeach</div>
  </div>
</section>

<section class="section section--alt"><div class="container container--narrow">
  <x-sec-head eyebrow="سوالات متداول" title="درباره‌ی نمونه‌کارها" :center="true" />
  <x-faq :items="$page['faq'] ?? []" :open-first="true" />
</div></section>
<x-cta-band title="پروژه‌ی بعدی می‌تواند سایت شما باشد" text="اگر نمونه‌ی مشابهی در ذهن دارید، همین حالا بگویید؛ همان را (بهتر) برای کسب‌وکار شما می‌سازیم." :primary="['دریافت مشاوره‌ی رایگان', '/contact-us/']" :secondary="['هزینه طراحی سایت', '/price-wordpress/']" />
@endsection
