@php($alt = ! empty($sec['alt']) ? ' section--alt' : '')
@php($sid = ! empty($sec['id']) ? ' id="' . e($sec['id']) . '"' : '')
<section class="section{{ $alt }}"{!! $sid !!}><div class="container">
  <div class="license reveal">
    <div class="license__img" data-lightbox="{{ amol_asset('img/team/business-license.webp') }}"><img src="{{ amol_asset('img/team/business-license.webp') }}" width="1000" height="708" loading="lazy" alt="پروانه‌ی کسب فناوری اطلاعات آمل نوین از اتحادیه‌ی خدمات رایانه‌ای و اطلاع‌رسانی شهرستان آمل"></div>
    <div>
      <x-sec-head :eyebrow="amol_or($sec, 'eyebrow', 'مجوز و اعتماد')" :title="amol_or($sec, 'title', 'پروانه‌ی کسب رسمی و قرارداد کتبی')" />
      <p class="muted">{{ $sec['text'] ?? '' }}</p>
      <ul class="checklist">@foreach (($sec['points'] ?? []) as $p)<li>@icon('check')<span>{{ $p }}</span></li>@endforeach</ul>
    </div>
  </div>
</div></section>
