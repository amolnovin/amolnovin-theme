@extends('layouts.app')

@section('content')
@php
  $t = $page['t'];
  $products = \App\Content\Repo::productBy();
  $projBy = \App\Content\Repo::projectBy();
  $typeBy = \App\Content\Repo::typeBy();
  $storeUrl = \App\Content\Repo::storeUrl();
@endphp
@include('partials.page-hero')
<section class="section" id="details">
  <div class="container">
    <div class="type-layout">
      <div class="type-main">

        <div class="type-block" id="why">
          <h2><span class="ico" style="--c:{{ $t['color'] }}">@icon($t['icon'])</span> چرا {{ $t['kw'] }} برای کسب‌وکار شما مهم است؟</h2>
          @foreach ($t['intro'] as $para)<p class="reveal">{{ $para }}</p>@endforeach
          <p class="small muted mb-0">مناسب برای:</p>
          <ul class="chip-row mt-2">@foreach ($t['audience'] as $a)<li class="chip chip--accent reveal">{{ $a }}</li>@endforeach</ul>
        </div>

        <div class="type-block" id="features">
          <h2><span class="ico" style="--c:{{ $t['color'] }}">@icon('sparkles')</span> امکانات پیشنهادی در {{ $t['title'] }}</h2>
          <ul class="grid grid--2">
            @foreach ($t['features'] as [$ic, $ti, $tx])
            <li class="card reveal" style="--d:{{ ($loop->index % 4) * 0.06 }}s"><span class="card__icon" style="--c:{{ $t['color'] }}">@icon($ic)</span><h3>{{ $ti }}</h3><p>{{ $tx }}</p></li>
            @endforeach
          </ul>
        </div>

        <div class="type-block" id="structure">
          <h2><span class="ico" style="--c:{{ $t['color'] }}">@icon('list-ordered')</span> ساختار پیشنهادی صفحات {{ str_replace('طراحی ', '', $t['title']) }}</h2>
          <p class="muted">این نقشه‌ی سایت نقطه‌ی شروع جلسه‌ی مشاوره است و با توجه به کسب‌وکار شما کم و زیاد می‌شود.</p>
          <ol class="wire">
            @foreach ($t['structure'] as $s)@php($parts = explode('|', $s))<li class="reveal">{{ $parts[0] }}@if (count($parts) > 1)<small>{{ $parts[1] }}</small>@endif</li>@endforeach
          </ol>
        </div>

        <div class="type-block" id="tech">
          <h2><span class="ico" style="--c:{{ $t['color'] }}">@icon('cpu')</span> فناوری‌ها و افزونه‌های پیشنهادی</h2>
          <ul class="chip-row">@foreach ($t['tech'] as $x)<li class="chip reveal">{{ $x }}</li>@endforeach</ul>
          @if (! empty($t['plugins']))
          <p class="mt-3 muted">افزونه‌های اختصاصی آمل نوین که برای این حوزه کاربرد دارند:</p>
          <div class="link-cards">
            @foreach ($t['plugins'] as $s)@if (isset($products[$s]))@php($pr = $products[$s])
            <a class="link-card reveal" href="{{ $storeUrl }}{{ $pr['slug'] }}" target="_blank" rel="noopener"><span class="type-tile__ico" style="--c:{{ $t['color'] }}">@icon($pr['icon'])</span><span><strong>{{ $pr['name'] }}</strong><small>{{ mb_substr($pr['desc'], 0, 70) }}…</small></span>@icon('external-link', 'i--go i--sm')</a>
            @endif @endforeach
          </div>
          @endif
        </div>

        <div class="type-block" id="seo">
          <h2><span class="ico" style="--c:{{ $t['color'] }}">@icon('chart-line')</span> سئو در {{ $t['title'] }}</h2>
          <p class="muted">سئو از همان ابتدا در ساختار سایت تعریف می‌شود. این‌ها مهم‌ترین بخش‌هایی است که برای {{ $t['kw'] }} اجرا می‌کنیم:</p>
          <ul class="grid grid--3">
            @foreach ($t['seo'] as [$ti, $tx])<li class="card reveal"><h3>{{ $ti }}</h3><p>{{ $tx }}</p></li>@endforeach
          </ul>
          <p class="mt-3 small muted">کلمات پیشنهادی این صفحه: @foreach (array_merge([$t['kw']], $t['kws']) as $k)<span class="chip">{{ $k }}</span> @endforeach</p>
        </div>

        <div class="type-block" id="price">
          <h2><span class="ico" style="--c:{{ $t['color'] }}">@icon('banknote')</span> زمان و هزینه‌ی {{ $t['kw'] }}</h2>
          <div class="facts-grid">
            <div class="fact"><small>@icon('clock', 'i--sm') زمان تحویل معمول</small><strong>{{ $t['time'] }}</strong></div>
            <div class="fact"><small>@icon('banknote', 'i--sm') قیمت</small><strong>از {{ $site['price_design'] }} میلیون تومان</strong></div>
            <div class="fact"><small>@icon('life-buoy', 'i--sm') پشتیبانی</small><strong>{{ $site['support_months'] }} ماه رایگان</strong></div>
            <div class="fact"><small>@icon('file-signature', 'i--sm') قرارداد</small><strong>کتبی و رسمی</strong></div>
          </div>
          <p class="mt-3">قیمت نهایی بر اساس تعداد صفحات، امکانات اختصاصی و زبان‌ها محاسبه می‌شود. برای برآورد سریع از <a href="{{ amol_url('/price-wordpress/#calculator') }}">ماشین‌حساب هزینه‌ی طراحی سایت</a> استفاده کنید یا <a href="{{ amol_url('/contact-us/') }}">مشاوره‌ی رایگان</a> بگیرید.</p>
        </div>

        @if (! empty($t['projects']))
        <div class="type-block" id="projects">
          <h2><span class="ico" style="--c:{{ $t['color'] }}">@icon('images')</span> نمونه‌کارهای مرتبط</h2>
          <div class="projects" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))">@foreach (array_slice($t['projects'], 0, 3) as $s)@if (isset($projBy[$s]))<x-project-card :p="$projBy[$s]" />@endif @endforeach</div>
        </div>
        @else
        <div class="type-block" id="projects">
          <h2><span class="ico" style="--c:{{ $t['color'] }}">@icon('images')</span> نمونه‌کار این حوزه</h2>
          <div class="callout">@icon('info')<p>نمونه‌کارهای مرتبط با {{ $t['name'] }} را متناسب با نیاز شما در جلسه‌ی مشاوره‌ی رایگان ارائه می‌کنیم. در همین حین می‌توانید <a href="{{ amol_url('/portfolio/') }}">همه‌ی نمونه‌کارهای ما</a> را ببینید.</p></div>
        </div>
        @endif

        <div class="type-block" id="faq">
          <h2><span class="ico" style="--c:{{ $t['color'] }}">@icon('circle-help')</span> سوالات متداول درباره‌ی {{ $t['kw'] }}</h2>
          <x-faq :items="$t['faq']" :open-first="true" />
        </div>

        <div class="type-block" id="related">
          <h2><span class="ico" style="--c:{{ $t['color'] }}">@icon('layout-grid')</span> انواع دیگر سایت</h2>
          <div class="link-cards">@foreach ($t['related'] as $s)@if (isset($typeBy[$s]))@php($o = $typeBy[$s])<a class="link-card" href="{{ amol_entity_url($o, 'type') }}"><span class="type-tile__ico" style="--c:{{ $o['color'] }}">@icon($o['icon'])</span><span><strong>{{ $o['title'] }}</strong><small>{{ $o['short'] }}</small></span>@icon('arrow-left', 'i--go i--sm')</a>@endif @endforeach</div>
        </div>
      </div>

      <aside class="type-aside" id="consult" aria-label="درخواست مشاوره">
        <div class="lead-card"><h2 style="font-size:1.15rem">مشاوره‌ی رایگان {{ $t['title'] }}</h2><p class="muted small">در {{ $site['response_time'] }} با شما تماس می‌گیریم.</p><x-lead-form id="f-type" :service="$t['title']" variant="compact" cta="ارسال درخواست" /></div>
        <div class="mini-card"><h3>همین حالا تماس بگیرید</h3><ul>
          <li><a href="tel:{{ $site['phone_intl'] }}">@icon('phone-call') <span dir="ltr">{{ $site['phone_display'] }}</span></a></li>
          <li><a href="{{ $site['whatsapp'] }}" target="_blank" rel="noopener">@icon('b-whatsapp') پیام در واتساپ</a></li>
          <li><a href="{{ amol_url('/web-design-amol/') }}">@icon('map-pin') جلسه‌ی حضوری در آمل</a></li></ul></div>
        <div class="mini-card"><h3>صفحات مرتبط</h3><ul>
          <li><a href="{{ amol_url('/services/web-design/') }}">@icon('layout-grid') همه‌ی انواع سایت</a></li>
          <li><a href="{{ amol_url('/wordpress-web-design/') }}">@icon('b-wordpress') طراحی سایت وردپرس</a></li>
          <li><a href="{{ amol_url('/services/coding/') }}">@icon('code-xml') طراحی سایت اختصاصی</a></li>
          <li><a href="{{ amol_url('/services/seo/') }}">@icon('chart-line') سئو و تولید محتوا</a></li>
          <li><a href="{{ amol_url('/price-wordpress/') }}">@icon('banknote') هزینه طراحی سایت</a></li></ul></div>
      </aside>
    </div>
  </div>
</section>
<x-cta-band :title="'سایت ' . $t['name'] . ' خود را با ما شروع کنید'" text="یک جلسه‌ی مشاوره‌ی رایگان؛ بررسی نیاز، ساختار پیشنهادی و برآورد شفاف زمان و هزینه، بدون هیچ تعهدی." :primary="['دریافت مشاوره‌ی رایگان', '#consult']" :secondary="['هزینه طراحی سایت', '/price-wordpress/']" />
@endsection
