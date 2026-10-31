@php
  $types = amol_types();
  $labels = amol_common('chooser_labels', []);
  $chooser = [];
  foreach ($types as $i => $t) {
      $chooser[(string) $i] = ['slug' => $t['slug'], 'title' => $t['title'], 'tldr' => $t['tldr']];
  }
@endphp
<section class="section section--alt" id="chooser"><div class="container container--narrow">
  <x-sec-head eyebrow="پیشنهاد هوشمند" title="کدام نوع سایت برای کسب‌وکار من مناسب است؟" desc="نوع کسب‌وکارتان را انتخاب کنید تا ساختار و امکانات پیشنهادی را ببینید." :center="true" />
  <div class="lead-card reveal" data-chooser="{{ wp_json_encode((object) $chooser, JSON_UNESCAPED_UNICODE) }}">
    <div class="field"><label for="chooser-sel">کسب‌وکار من:</label><select id="chooser-sel"><option value="">— انتخاب کنید —</option>@foreach ($types as $i => $t)<option value="{{ $i }}">{{ $labels[$t['slug']] ?? $t['title'] }}</option>@endforeach</select></div>
    <div class="chooser__out" aria-live="polite"></div>
  </div></div></section>
