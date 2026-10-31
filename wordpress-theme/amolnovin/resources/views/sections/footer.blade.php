@php
  $hf = \App\Support\Options::section('header_footer');
  $about = trim((string) ($hf['footer_about'] ?? ''));
  $fServices = \App\Support\Menus::tree('footer_services');
  $fTypes = \App\Support\Menus::tree('footer_types');
  $fQuick = \App\Support\Menus::tree('footer_quick');
  $cities = amol_cities();
  $logo = has_custom_logo() ? wp_get_attachment_image_url((int) get_theme_mod('custom_logo'), 'full') : amol_asset('img/brand/avn_new_logo-512-512.webp');
  $privacy = get_privacy_policy_url() ?: amol_url('/privacy-policy/');
@endphp
<footer class="site-footer">
  <div class="container footer__grid">
    <div class="footer__brand">
      <a class="brand" href="{{ home_url('/') }}"><img class="footer__logo" src="{{ $logo }}" width="84" height="84" alt="لوگوی رسمی {{ $site['name'] }}" loading="lazy"><span class="brand__text"><strong>{{ $site['short'] }}</strong><small>فناوری اطلاعات</small></span></a>
      <p>{{ $about !== '' ? $about : $site['name'] . ' از سال ' . $site['founded_j'] . ' در آمل مازندران؛ طراحی سایت وردپرس و اختصاصی، پشتیبانی سایت، سئو و تولید محتوا و خدمات امنیت و سرعت، با پروانه‌ی کسب رسمی و قرارداد کتبی.' }}</p>
      <ul class="footer__contact">
        <li>@icon('map-pin')<span>{{ $site['address'] }}</span></li>
        <li>@icon('phone-call')<a href="tel:{{ $site['phone_intl'] }}" dir="ltr">{{ $site['phone_display'] }}</a></li>
        <li>@icon('mail')<a href="mailto:{{ $site['email'] }}" dir="ltr">{{ $site['email'] }}</a></li>
      </ul>
      <div class="social">
        <a href="{{ $site['whatsapp'] }}" rel="noopener" target="_blank" aria-label="واتساپ">@icon('b-whatsapp')</a>
        @foreach (($site['social'] ?? []) as $k => $v)@if ($v)<a href="{{ esc_url($v) }}" rel="noopener me" target="_blank" aria-label="{{ $k }}">@icon('b-' . $k)</a>@endif @endforeach
        <a href="mailto:{{ $site['email'] }}" aria-label="ایمیل">@icon('mail')</a>
      </div>
    </div>
    <div>
      <h2 class="footer__title">خدمات</h2>
      <ul class="footer__list">@foreach ($fServices as $i)<li><a href="{{ esc_url($i['url']) }}">{{ $i['title'] }}</a></li>@endforeach</ul>
    </div>
    <div>
      <h2 class="footer__title">انواع سایت</h2>
      <ul class="footer__list">@foreach ($fTypes as $i)<li><a href="{{ esc_url($i['url']) }}">@if ($i['strong'])<strong>{{ $i['title'] }}</strong>@else{{ $i['title'] }}@endif</a></li>@endforeach</ul>
    </div>
    <div>
      <h2 class="footer__title">دسترسی سریع</h2>
      <ul class="footer__list">
        @foreach ($fQuick as $i)<li><a href="{{ esc_url($i['url']) }}">{{ $i['title'] }}</a></li>@endforeach
      </ul>
    </div>
  </div>
  <div class="container footer__cities"><strong>خدمات آنلاین و حضوری در:</strong>{{ implode('، ', $cities) }} و سراسر مازندران و ایران.</div>
  <div class="container footer__bottom">
    <span>© {{ $site['jalali_year'] }} {{ $site['name'] }} — {{ trim((string) ($hf['footer_copyright'] ?? '')) !== '' ? $hf['footer_copyright'] : 'تمامی حقوق محفوظ است.' }}</span>
    <span class="footer__legal"><a href="{{ esc_url($privacy) }}">حریم خصوصی</a><a href="{{ amol_url('/sitemap/') }}">نقشه‌ی سایت</a><a href="{{ esc_url(get_feed_link()) }}">خوراک RSS</a></span>
  </div>
</footer>
<div class="fab" aria-label="دسترسی سریع">
  <a class="fab__btn fab__btn--wa" href="{{ $site['whatsapp'] }}?text={{ rawurlencode('سلام، برای مشاوره‌ی طراحی سایت پیام می‌دهم.') }}" rel="noopener" target="_blank" aria-label="گفتگو در واتساپ">@icon('b-whatsapp')<span class="fab__label">گفتگو در واتساپ</span></a>
  <a class="fab__btn fab__btn--call" href="tel:{{ $site['phone_intl'] }}" aria-label="تماس تلفنی">@icon('phone-call')<span class="fab__label">تماس تلفنی</span></a>
  @if (! empty($hf['back_to_top']))<button class="fab__btn fab__btn--top" type="button" data-to-top aria-label="بازگشت به بالای صفحه">@icon('arrow-up')</button>@endif
</div>
<div class="mobile-bar">
  <a class="btn btn--ghost btn--sm" href="tel:{{ $site['phone_intl'] }}">@icon('phone-call') تماس</a>
  <a class="btn btn--wa btn--sm" href="{{ $site['whatsapp'] }}" rel="noopener" target="_blank">@icon('b-whatsapp') واتساپ</a>
  <a class="btn btn--cta btn--sm" href="{{ amol_url('/contact-us/') }}">مشاوره‌ی رایگان</a>
</div>
