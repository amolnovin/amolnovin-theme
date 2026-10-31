@php($alt = ! empty($sec['alt']) ? ' section--alt' : '')
@php($sid = ! empty($sec['id']) ? ' id="' . e($sec['id']) . '"' : '')
<section class="section{{ $alt }}"{!! $sid !!}><div class="container">
  <x-sec-head :eyebrow="$sec['eyebrow'] ?? null" :title="$sec['title'] ?? ''" :desc="$sec['desc'] ?? null" :center="! empty($sec['center'])" />
  <div class="vs">
    @foreach ($sec['cols'] as $c)<div class="vs__col{{ ! empty($c['win']) ? ' vs__col--win' : '' }} reveal"><h3>@icon($c['icon']) {{ $c['title'] }}</h3><p class="muted">{{ $c['text'] }}</p><ul class="checklist">@foreach ($c['points'] as $p)<li>@icon('check')<span>{{ $p }}</span></li>@endforeach</ul></div>@endforeach
  </div>
</div></section>
