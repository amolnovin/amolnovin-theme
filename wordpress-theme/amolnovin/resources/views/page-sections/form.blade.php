@php($points = amol_or($sec, 'points', ['پاسخ در ' . $site['response_time'], 'بررسی نیاز و ارائه‌ی پیشنهاد بدون تعهد', 'برآورد زمان و هزینه‌ی شفاف', 'امکان جلسه‌ی حضوری در دفتر آمل']))
<section class="section section--alt"{!! ' id="' . e(amol_or($sec, 'id', 'consult')) . '"' !!}><div class="container">
  <div class="split split--wide-right">
    <div class="reveal">
      <x-sec-head :eyebrow="amol_or($sec, 'eyebrow', 'مشاوره‌ی رایگان')" :title="amol_or($sec, 'title', 'پروژه‌ی خود را با ما در میان بگذارید')" :desc="$sec['desc'] ?? null" />
      <ul class="checklist">@foreach ($points as $p)<li>@icon('check')<span>{{ $p }}</span></li>@endforeach</ul>
    </div>
    <div class="lead-card reveal"><h3>{{ amol_or($sec, 'form_title', 'درخواست مشاوره‌ی رایگان') }}</h3><p class="muted small">فقط چند ثانیه؛ بقیه‌اش با ما.</p><x-lead-form :id="amol_or($sec, 'fid', 'f-' . $idx)" :service="$sec['service'] ?? ''" variant="full" /></div>
  </div>
</div></section>
