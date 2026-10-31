@php($alt = ! empty($sec['alt']) ? ' section--alt' : '')
@php($sid = ! empty($sec['id']) ? ' id="' . e($sec['id']) . '"' : '')
<section class="section section--tight{{ $alt }}"{!! $sid !!}><div class="container">
  <x-sec-head :eyebrow="$sec['eyebrow'] ?? null" :title="$sec['title'] ?? ''" :desc="$sec['desc'] ?? null" :center="! empty($sec['center'])" />
  <ul class="chip-row" style="{{ ! empty($sec['center']) ? 'justify-content:center' : '' }}">@foreach ($sec['items'] as $t)<li class="tech reveal" style="--tc:{{ $t[2] }}">@icon($t[1]) {{ $t[0] }}</li>@endforeach</ul>
</div></section>
