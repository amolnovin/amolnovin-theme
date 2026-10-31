@php($alt = ! empty($sec['alt']) ? ' section--alt' : '')
@php($sid = ! empty($sec['id']) ? ' id="' . e($sec['id']) . '"' : '')
<section class="section{{ $alt }}"{!! $sid !!}><div class="container">
  <x-sec-head :eyebrow="$sec['eyebrow'] ?? null" :title="$sec['title'] ?? ''" :desc="$sec['desc'] ?? null" :center="! empty($sec['center'])" />
  <div class="plans">
    @foreach ($sec['items'] as $p)
    <article class="plan{{ ! empty($p['pop']) ? ' plan--pop' : '' }} reveal" style="--d:{{ $loop->index * 0.08 }}s">
      @if (! empty($p['flag']))<span class="plan__flag">{{ $p['flag'] }}</span>@endif
      <h3 class="plan__name">{{ $p['name'] }}</h3>
      <p class="plan__desc">{{ $p['desc'] }}</p>
      <div class="plan__price">{{ $p['price'] }}<small>{{ $p['unit'] }}</small></div>
      <ul class="plan__list">@foreach ($p['features'] as $f)<li>@icon('check')<span>{{ $f }}</span></li>@endforeach</ul>
      <a class="btn {{ ! empty($p['pop']) ? 'btn--cta' : 'btn--primary' }}" href="{{ amol_url(amol_or($p, 'href', '/contact-us/')) }}">{{ amol_or($p, 'cta', 'دریافت مشاوره و قیمت') }} @icon('arrow-left', 'i--arrow')</a>
    </article>
    @endforeach
  </div>
  @if (! empty($sec['note']))<p class="plan__note mt-3">{!! amol_rl($sec['note']) !!}</p>@endif
</div></section>
