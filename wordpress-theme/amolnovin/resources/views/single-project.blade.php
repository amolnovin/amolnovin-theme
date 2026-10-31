@extends('layouts.app')

@php
  $p = $page['p'];
  $svc = amol_project_services();
  $cats = amol_project_cats();
  $typeBy = \App\Content\Repo::typeBy();
  $products = \App\Content\Repo::productBy();
  $projBy = \App\Content\Repo::projectBy();
  $storeUrl = \App\Content\Repo::storeUrl();
  $type = $typeBy[$p['type'] ?? ''] ?? null;
  $full = ! empty($p['full']);
@endphp

@push('head')
<link rel="preload" as="image" href="{{ amol_img($p, 'portfolio', '-lg') }}" type="image/webp">
@endpush

@section('content')
<section class="page-hero">
  <div class="container">
    @include('partials.breadcrumb', ['crumbs' => $page['crumbs']])
    <div class="page-hero__grid">
      <div>
        <span class="eyebrow">{{ $cats[$p['cat']] ?? '' }} · مطالعه‌ی موردی</span>
        <h1>{{ $p['title'] }}: <em>{{ $page['h1_suffix'] }}</em></h1>
        <p class="lead">{{ $p['overview'] ?? '' }}</p>
        <ul class="chip-row">@foreach ($p['services'] as $s)<li class="chip">{{ $svc[$s] }}</li>@endforeach @foreach ($p['tech'] as $t)<li class="chip">{{ $t }}</li>@endforeach</ul>
        <div class="btn-row">
          @if (! empty($p['url']))<a class="btn btn--cta btn--lg" href="{{ $p['url'] }}" target="_blank" rel="noopener">بازدید زنده‌ی سایت @icon('external-link')</a>@endif
          <a class="btn btn--light btn--lg" href="#consult">پروژه‌ی مشابه می‌خواهم</a>
        </div>
      </div>
      <div class="page-hero__visual">
        <div class="browser">
          <div class="browser__bar"><i></i><i></i><i></i><span class="browser__url">{{ amol_or($p, 'domain', str_replace('https://', '', $site['url'])) }}</span></div>
          <div class="browser__view{{ $full ? ' browser__view--scroll' : '' }}" @if ($full) data-lightbox="{{ amol_asset('img/portfolio/' . $p['full'] . '.webp') }}" @endif>
            <img src="{{ $full ? amol_asset('img/portfolio/' . $p['full'] . '.webp') : amol_img($p, 'portfolio', '-lg') }}" width="{{ $full ? 800 : 1100 }}" height="{{ $full ? 3000 : 720 }}" alt="نمونه‌کار طراحی سایت {{ $p['title'] }}" fetchpriority="high">
            @if ($full)<span class="browser__hint">@icon('mouse-pointer-click', 'i--sm') نشانگر را روی تصویر نگه دارید تا صفحه اسکرول شود</span>@endif
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<section class="section section--tight"><div class="container"><div class="facts-grid">
  <div class="fact"><small>@icon('building-2', 'i--sm') کارفرما</small><strong>{{ $p['title'] }}</strong></div>
  <div class="fact"><small>@icon('tag', 'i--sm') دسته‌بندی</small><strong>{{ $cats[$p['cat']] ?? '' }}</strong></div>
  <div class="fact"><small>@icon('layers', 'i--sm') خدمات آمل نوین</small><strong>@foreach ($p['services'] as $s){{ $svc[$s] }}@if (! $loop->last)، @endif @endforeach</strong></div>
  <div class="fact"><small>@icon('cpu', 'i--sm') فناوری</small><strong>{{ implode('، ', $p['tech']) }}</strong></div>
  @if (! empty($p['start']))<div class="fact"><small>@icon('calendar-days', 'i--sm') شروع همکاری</small><strong>{{ $p['start'] }}</strong></div>@endif
  @if (! empty($p['domain']))<div class="fact"><small>@icon('globe', 'i--sm') آدرس سایت</small><strong dir="ltr">{{ $p['domain'] }}</strong></div>@endif
</div></div></section>

<section class="section">
  <div class="container">
    <div class="case-grid">
      <div>
        <div class="case-block"><h2><span class="n">@icon('file-text')</span> معرفی پروژه‌ی {{ $p['title'] }}</h2><p class="reveal">{{ $p['overview'] ?? '' }}</p></div>
        <div class="case-block"><h2><span class="n">@icon('target')</span> چالش</h2><p class="reveal">{{ $p['challenge'] ?? '' }}</p></div>
        <div class="case-block"><h2><span class="n">@icon('lightbulb')</span> راه‌حل و کارهای انجام‌شده</h2>
          <ul class="checklist reveal">@foreach (($p['solution'] ?? []) as $s)<li>@icon('check')<span>{{ $s }}</span></li>@endforeach</ul></div>
        <div class="case-block"><h2><span class="n">@icon('package-check')</span> خروجی‌های تحویل‌شده</h2>
          <div class="chip-row reveal">@foreach (($p['deliver'] ?? []) as $s)<span class="chip chip--accent">{{ $s }}</span>@endforeach</div></div>
        <div class="case-block"><h2><span class="n">@icon('badge-check')</span> نتیجه</h2><p class="reveal">{{ $p['outcome'] ?? '' }}</p>
          <div class="callout mt-3">@icon('info')<p>برای حفظ دقت، در این صفحه فقط اطلاعات تأییدشده‌ی پروژه آمده است. آمار و گزارش عملکرد (ترافیک، فروش، سرعت) را می‌توانید در جلسه‌ی مشاوره درخواست کنید.</p></div></div>
        @if (! empty($p['plugin']) && isset($products[$p['plugin']]))@php($pr = $products[$p['plugin']])
        <div class="case-block"><h2><span class="n">@icon('package')</span> افزونه‌ی اختصاصی این پروژه</h2>
          <a class="link-card reveal" href="{{ $storeUrl }}{{ $pr['slug'] }}" target="_blank" rel="noopener"><span class="type-tile__ico">@icon($pr['icon'])</span><span><strong>{{ $pr['name'] }}</strong><small>{{ $pr['desc'] }}</small></span>@icon('external-link', 'i--go i--sm')</a></div>
        @endif
      </div>
      <aside class="case-aside" id="consult">
        <div class="lead-card" style="padding:1.4rem"><h2 style="font-size:1.1rem">پروژه‌ای مشابه می‌خواهید؟</h2><p class="muted small">مشاوره‌ی رایگان؛ پاسخ در {{ $site['response_time'] }}.</p><x-lead-form id="f-proj" :service="$type['title'] ?? ''" variant="compact" cta="ارسال درخواست" /></div>
        <div class="mini-card"><h3>خدمات مرتبط</h3><ul>
          @if ($type)<li><a href="{{ amol_entity_url($type, 'type') }}">@icon($type['icon']) {{ $type['title'] }}</a></li>@endif
          <li><a href="{{ amol_url('/services/site-support/') }}">@icon('life-buoy') پشتیبانی سایت</a></li>
          <li><a href="{{ amol_url('/services/seo/') }}">@icon('chart-line') سئو و تولید محتوا</a></li>
          <li><a href="{{ amol_url('/price-wordpress/') }}">@icon('banknote') هزینه طراحی سایت</a></li></ul></div>
      </aside>
    </div>
  </div>
</section>

<section class="section section--tight"><div class="container">
  <nav class="pager" aria-label="پروژه‌ی قبلی و بعدی">
    @if (! empty($page['prev']))<a href="{{ amol_entity_url($page['prev'], 'project') }}"><small>@icon('arrow-right', 'i--sm') پروژه‌ی قبلی</small><strong>{{ $page['prev']['title'] }}</strong></a>@else<span></span>@endif
    @if (! empty($page['next']))<a class="is-next" href="{{ amol_entity_url($page['next'], 'project') }}"><small>پروژه‌ی بعدی @icon('arrow-left', 'i--sm')</small><strong>{{ $page['next']['title'] }}</strong></a>@endif
  </nav>
</div></section>
<section class="section section--alt"><div class="container">
  <x-sec-head eyebrow="نمونه‌کارهای مرتبط" title="پروژه‌های دیگر از همین دسته" />
  <div class="projects">@foreach (($page['related'] ?? []) as $s)@if (isset($projBy[$s]))<x-project-card :p="$projBy[$s]" />@endif @endforeach</div>
</div></section>
<x-cta-band title="سایت شما هم می‌تواند اینگونه دیده شود" text="یک جلسه‌ی مشاوره‌ی رایگان بگیرید تا نیاز شما را بشنویم و بهترین ساختار را پیشنهاد دهیم." :primary="['دریافت مشاوره‌ی رایگان', '#consult']" :secondary="['همه‌ی نمونه‌کارها', '/portfolio/']" />
@endsection
