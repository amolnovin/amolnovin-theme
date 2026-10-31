@php($alt = ! empty($sec['alt']) ? ' section--alt' : '')
@php($sid = ! empty($sec['id']) ? ' id="' . e($sec['id']) . '"' : '')
@php($all = amol_types())
@php($list = ! empty($sec['limit']) ? array_slice($all, 0, (int) $sec['limit']) : $all)
<section class="section{{ $alt }}"{!! $sid !!}><div class="container">
  <x-sec-head :eyebrow="$sec['eyebrow'] ?? null" :title="$sec['title'] ?? ''" :desc="$sec['desc'] ?? null" :center="! empty($sec['center'])" />
  <div class="types">@foreach ($list as $t)<x-type-tile :t="$t" />@endforeach</div>
  @if (! empty($sec['link']))<p class="center mt-3"><a class="btn btn--ghost" href="{{ amol_url('/services/web-design/') }}">مشاهده‌ی همه‌ی انواع سایت @icon('arrow-left', 'i--arrow')</a></p>@endif
</div></section>
