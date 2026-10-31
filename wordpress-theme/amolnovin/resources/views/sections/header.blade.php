@php
  $nav = \App\Support\Menus::tree('primary_navigation');
  $mobile = \App\Support\Menus::tree('mobile_navigation');
  $path = $page['path'] ?? '';
  $typesCount = count(\App\Content\Repo::types());
  $moreUsed = false;
  $hf = \App\Support\Options::section('header_footer');
  $announce = trim((string) ($hf['announcement'] ?? ''));
  $announceUrl = trim((string) ($hf['announcement_url'] ?? ''));
  $ctaLabel = trim((string) ($hf['cta_label'] ?? '')) ?: 'مشاوره‌ی رایگان';
  $ctaUrl = trim((string) ($hf['cta_url'] ?? '')) ?: '/contact-us/';
@endphp
<a class="skip-link" href="#content">پرش به محتوای اصلی</a>
<div class="progress-bar" aria-hidden="true"><span></span></div>
@if ($announce !== '')
<div class="announce" data-announce role="region" aria-label="اعلان سایت">
  <div class="container announce__in">
    <p>{!! nl2br(esc_html($announce)) !!}</p>
    @if ($announceUrl !== '')<a class="announce__link" href="{{ amol_url($announceUrl) }}">بیشتر بدانید @icon('arrow-left', 'i--sm')</a>@endif
    @if (! empty($hf['announcement_dismiss']))
    <button class="announce__close" type="button" data-announce-close aria-label="بستن اعلان">@icon('x')</button>
    @endif
  </div>
</div>
@endif
@if (! empty($hf['topbar']))
<div class="topbar">
  <div class="container topbar__in">
    <div class="topbar__group">
      <span class="topbar__item">@icon('map-pin') {{ $site['address'] }}</span>
      <a class="topbar__item" href="mailto:{{ $site['email'] }}">@icon('mail') <span dir="ltr">{{ $site['email'] }}</span></a>
    </div>
    <div class="topbar__group">
      <span class="topbar__live">پاسخ‌گویی ۲۴/۷</span>
      <a class="topbar__item" href="tel:{{ $site['phone_intl'] }}">@icon('phone-call') <span dir="ltr">{{ $site['phone_display'] }}</span></a>
    </div>
  </div>
</div>
@endif
<header class="site-header" id="top">
  <div class="container header__in">
    <a class="brand" href="{{ home_url('/') }}" aria-label="{{ $site['name'] }} — صفحه‌ی اصلی">
      <img class="brand__mark brand__mark--dark" src="{{ amol_asset('img/brand/logo-mark.svg') }}" width="58" height="29" alt="لوگوی {{ $site['short'] }}">
      <img class="brand__mark brand__mark--light" src="{{ amol_asset('img/brand/logo-mark-light.svg') }}" width="58" height="29" alt="" aria-hidden="true">
      <span class="brand__text"><strong>{{ $site['short'] }}</strong><small>فناوری اطلاعات</small></span>
    </a>
    <nav class="nav" aria-label="منوی اصلی">
      <ul class="nav__list">
        @foreach ($nav as $item)
          @if (empty($item['children']))
            <li><a class="nav__link" href="{{ esc_url($item['url']) }}" @if (\App\Support\Menus::isCurrent($item, $page)) aria-current="page" @endif @if ($item['target']) target="{{ $item['target'] }}" rel="noopener" @endif>{{ $item['title'] }}</a></li>
          @elseif ($item['layout'] === 'services')
            @php($cur = str_starts_with($path, '/services/') && ! str_starts_with($path, '/services/web-design/'))
            <li class="has-mega">
              <button class="nav__link {{ $cur ? 'is-current' : '' }}" type="button" aria-expanded="false" aria-controls="mega-services">{{ $item['title'] }} @icon('chevron-down')</button>
              <div class="mega" id="mega-services"><div class="container"><div class="mega__panel mega__panel--services">
                <div class="mega__cols mega__cols--span2">
                  <p class="mega__title">خدمات آمل نوین</p>
                  @foreach ($item['children'] as $s)
                  <a class="mega__link" href="{{ esc_url($s['url']) }}"><span class="mega__ico" style="--c:{{ $s['color'] }}">@icon($s['icon'])</span><span><strong>{{ $s['title'] }}</strong><small>{{ $s['desc'] }}</small></span></a>
                  @endforeach
                </div>
                <aside class="mega__promo">
                  <div><span class="badge badge--amber">مشاوره‌ی رایگان</span></div>
                  <h4>سایتتان را از چه نقطه‌ای شروع کنیم؟</h4>
                  <p>در یک جلسه‌ی کوتاه نیاز شما را بررسی می‌کنیم و بدون تعهد، پیشنهاد و برآورد می‌دهیم.</p>
                  <div class="price-from"><small>طراحی سایت از</small> {{ $site['price_design'] }} میلیون تومان</div>
                  <a class="btn btn--cta btn--sm" href="{{ amol_url('/contact-us/') }}">@icon('message-circle') درخواست مشاوره</a>
                </aside>
              </div></div></div>
            </li>
          @elseif ($item['layout'] === 'types')
            @php($cur = str_starts_with($path, '/services/web-design/'))
            <li class="has-mega">
              <button class="nav__link {{ $cur ? 'is-current' : '' }}" type="button" aria-expanded="false" aria-controls="mega-types">{{ $item['title'] }} @icon('chevron-down')</button>
              <div class="mega" id="mega-types"><div class="container"><div class="mega__panel mega__panel--types">
                <div class="mega__cols mega__cols--4">
                  <p class="mega__title">طراحی سایت برای کسب‌وکار شما</p>
                  @foreach ($item['children'] as $t)
                  <a class="mega__link mega__link--sm" href="{{ esc_url($t['url']) }}"><span class="mega__ico" style="--c:{{ $t['color'] }}">@icon($t['icon'])</span><span><strong>{{ $t['title'] }}</strong></span></a>
                  @endforeach
                </div>
                <aside class="mega__promo">
                  <h4>کدام نوع سایت مناسب شماست؟</h4>
                  <p>نوع کسب‌وکارتان را انتخاب کنید تا بهترین ساختار سایت و امکانات پیشنهادی را ببینید.</p>
                  <a class="btn btn--cta btn--sm" href="{{ amol_url('/services/web-design/#chooser') }}">@icon('wand-sparkles') پیشنهاد هوشمند</a>
                </aside>
                <div class="mega__foot"><span class="muted small">{{ $typesCount }} نوع سایت، هر کدام با صفحه‌ی اختصاصی</span><a class="link-arrow" href="{{ amol_url('/services/web-design/') }}">مشاهده‌ی همه‌ی انواع سایت @icon('arrow-left')</a></div>
              </div></div></div>
            </li>
          @else
            @php($megaId = ! $moreUsed ? 'mega-more' : 'mega-menu-' . $item['id'])
            @php($moreUsed = true)
            <li class="has-mega has-mega--menu">
              <button class="nav__link" type="button" aria-expanded="false" aria-controls="{{ $megaId }}">{{ $item['title'] }} @icon('chevron-down')</button>
              <div class="mega mega--menu" id="{{ $megaId }}"><div class="mega__panel mega__panel--menu">
                <div class="mega__cols mega__cols--1">
                  @foreach ($item['children'] as $c)<a class="mega__link mega__link--sm" href="{{ esc_url($c['url']) }}"><span class="mega__ico">@icon($c['icon'])</span><span><strong>{{ $c['title'] }}</strong></span></a>@endforeach
                </div>
              </div></div>
            </li>
          @endif
        @endforeach
      </ul>
    </nav>
    <div class="header__actions">
      <button class="icon-btn" type="button" data-search-open aria-label="جست‌وجو در سایت (Ctrl+K)" title="جست‌وجو">@icon('search')</button>
      <button class="icon-btn theme-toggle" type="button" data-theme-toggle aria-label="تغییر حالت روشن و تاریک" title="حالت تیره / روشن">@icon('moon', 'i-moon')@icon('sun', 'i-sun')</button>
      <a class="btn btn--cta btn--sm" href="{{ amol_url($ctaUrl) }}">@icon('message-circle') {{ $ctaLabel }}</a>
      <button class="icon-btn burger" type="button" data-menu-open aria-label="باز کردن منو" aria-controls="drawer" aria-expanded="false">@icon('menu')</button>
    </div>
  </div>
</header>
<div class="drawer" id="drawer" aria-hidden="true">
  <div class="drawer__backdrop" data-menu-close></div>
  <div class="drawer__panel" role="dialog" aria-modal="true" aria-label="منوی موبایل">
    <div class="drawer__head">
      <a class="brand" href="{{ home_url('/') }}"><img class="brand__mark brand__mark--dark" src="{{ amol_asset('img/brand/logo-mark.svg') }}" width="52" height="26" alt=""><img class="brand__mark brand__mark--light" src="{{ amol_asset('img/brand/logo-mark-light.svg') }}" width="52" height="26" alt=""><span class="brand__text"><strong>{{ $site['short'] }}</strong></span></a>
      <button class="icon-btn" type="button" data-menu-close aria-label="بستن منو">@icon('x')</button>
    </div>
    <div class="drawer__body">
      @foreach ($mobile as $item)
        @if (! empty($item['children']))
          <details class="drawer__group"><summary>{{ $item['title'] }} @icon('chevron-down')</summary>
            <div class="drawer__sub">@foreach ($item['children'] as $c)<a href="{{ esc_url($c['url']) }}">@if ($c['strong'])<strong>{{ $c['title'] }} @icon($c['icon'])</strong>@else @icon($c['icon']) {{ $c['title'] }}@endif</a>@endforeach</div></details>
        @else
          <a class="drawer__link" href="{{ esc_url($item['url']) }}">{{ $item['title'] }}</a>
        @endif
      @endforeach
    </div>
    <div class="drawer__foot">
      <a class="btn btn--cta btn--block" href="tel:{{ $site['phone_intl'] }}">@icon('phone-call') تماس: <span dir="ltr">{{ $site['phone_display'] }}</span></a>
      <a class="btn btn--wa btn--block" href="{{ $site['whatsapp'] }}" rel="noopener" target="_blank">@icon('b-whatsapp') پیام در واتساپ</a>
    </div>
  </div>
</div>
