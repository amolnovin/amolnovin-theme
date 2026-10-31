@php($alt = ! empty($sec['alt']) ? ' section--alt' : '')
@php($sid = ! empty($sec['id']) ? ' id="' . e($sec['id']) . '"' : '')
<section class="section{{ $alt }}"{!! $sid !!}><div class="container container--narrow">
  <x-sec-head :eyebrow="$sec['eyebrow'] ?? null" :title="$sec['title'] ?? ''" :desc="$sec['desc'] ?? null" />
  <ul class="timeline">@foreach ($sec['items'] as [$dt, $ti, $tx])<li class="reveal"><small>{{ $dt }}</small><h3>{{ $ti }}</h3><p>{{ $tx }}</p></li>@endforeach</ul>
</div></section>
