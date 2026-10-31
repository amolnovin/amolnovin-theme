@php($alt = ! empty($sec['alt']) ? ' section--alt' : '')
@php($sid = ! empty($sec['id']) ? ' id="' . e($sec['id']) . '"' : '')
<section class="section{{ $alt }}"{!! $sid !!}><div class="container container--narrow">
  <x-sec-head :eyebrow="$sec['eyebrow'] ?? null" :title="$sec['title'] ?? ''" :desc="$sec['desc'] ?? null" :center="true" />
  <div class="modules">
    @foreach ($sec['items'] as [$ti, $tag, $pts])
    <details class="module reveal"@if ($loop->first) open @endif><summary><h3>{{ $ti }}</h3><span class="chip">{{ $tag }}</span>@icon('chevron-down')</summary>
      <div class="module__body"><ul>@foreach ($pts as $p)<li>{{ $p }}</li>@endforeach</ul></div></details>
    @endforeach
  </div>
</div></section>
