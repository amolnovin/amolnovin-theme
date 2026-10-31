@php($alt = ! empty($sec['alt']) ? ' section--alt' : '')
@php($sid = ! empty($sec['id']) ? ' id="' . e($sec['id']) . '"' : '')
<section class="section{{ $alt }}"{!! $sid !!}><div class="container container--narrow">
  <x-sec-head :eyebrow="amol_or($sec, 'eyebrow', 'سوالات متداول')" :title="amol_or($sec, 'title', 'پاسخ به پرسش‌های پرتکرار')" :desc="$sec['desc'] ?? null" :center="true" />
  <x-faq :items="$sec['items']" :open-first="! empty($sec['open_first'])" />
</div></section>
