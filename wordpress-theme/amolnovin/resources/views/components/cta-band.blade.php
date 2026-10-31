@props(['title', 'text', 'primary' => null, 'secondary' => null])
@php
  $pTxt = $primary[0] ?? 'دریافت مشاوره‌ی رایگان';
  $pUrl = $primary[1] ?? '/contact-us/';
@endphp
<section class="section section--tight" aria-label="دعوت به اقدام">
  <div class="container">
    <div class="cta-band reveal">
      <div>
        <h2>{!! $title !!}</h2>
        <p>{{ $text }}</p>
        <div class="btn-row">
          <a class="btn btn--cta btn--lg" href="{{ amol_url($pUrl) }}">@icon('message-circle') {{ $pTxt }}</a>
          @if ($secondary)<a class="btn btn--light btn--lg" href="{{ amol_url($secondary[1]) }}">{{ $secondary[0] }}</a>@endif
        </div>
      </div>
      <div class="cta-band__side">
        <a class="contact-line" href="tel:{{ $site['phone_intl'] }}">@icon('phone-call')<span><small>تماس مستقیم</small><span dir="ltr">{{ $site['phone_display'] }}</span></span></a>
        <a class="contact-line" href="{{ $site['whatsapp'] }}" target="_blank" rel="noopener">@icon('b-whatsapp')<span><small>پیام در واتساپ</small>پاسخ در {{ $site['response_time'] }}</span></a>
        <a class="contact-line" href="mailto:{{ $site['email'] }}">@icon('mail')<span><small>ایمیل</small><span dir="ltr">{{ $site['email'] }}</span></span></a>
      </div>
    </div>
  </div>
</section>
