@php($alt = ! empty($sec['alt']) ? ' section--alt' : '')
@php($dark = ! empty($sec['dark']) ? ' section--dark' : '')
@php($sid = ! empty($sec['id']) ? ' id="' . e($sec['id']) . '"' : '')
<section class="section{{ $alt }}{{ $dark }}"{!! $sid !!}><div class="container">
  <x-sec-head :eyebrow="$sec['eyebrow'] ?? null" :title="$sec['title'] ?? ''" :desc="$sec['desc'] ?? null" :center="! empty($sec['center'])" />
  <ul class="grid grid--{{ amol_or($sec, 'cols', 3) }}">
    @foreach ($sec['items'] as $it)
    <li class="card{{ ! empty($it['href']) ? ' card--hover' : '' }} reveal" style="--d:{{ ($loop->index % 6) * 0.06 }}s">
      <span class="card__icon" style="--c:{{ amol_or($it, 'color', '#0e958c') }}">@icon($it['icon'])</span>
      <h3>{{ $it['title'] }}</h3>
      <p>{!! amol_rl($it['text'] ?? '') !!}</p>
      @if (! empty($it['href']))<a class="card__link" href="{{ amol_url($it['href']) }}">{{ amol_or($it, 'cta', 'بیشتر بخوانید') }} @icon('arrow-left')</a>@endif
    </li>
    @endforeach
  </ul>
</div></section>
