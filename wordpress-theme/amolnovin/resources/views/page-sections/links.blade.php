@php($alt = ! empty($sec['alt']) ? ' section--alt' : '')
@php($sid = ! empty($sec['id']) ? ' id="' . e($sec['id']) . '"' : '')
<section class="section section--tight{{ $alt }}"{!! $sid !!}><div class="container">
  <x-sec-head :eyebrow="$sec['eyebrow'] ?? null" :title="$sec['title'] ?? ''" :desc="$sec['desc'] ?? null" :center="! empty($sec['center'])" />
  <div class="link-cards">
    @foreach ($sec['items'] as $it)<a class="link-card reveal" href="{{ amol_url($it['href']) }}">@if (! empty($it['icon']))<span class="type-tile__ico" style="--c:{{ amol_or($it, 'color', '#0e958c') }}">@icon($it['icon'])</span>@endif<span><strong>{{ $it['title'] }}</strong>@if (! empty($it['text']))<small>{{ $it['text'] }}</small>@endif</span>@icon('arrow-left', 'i--go i--sm')</a>@endforeach
  </div>
</div></section>
