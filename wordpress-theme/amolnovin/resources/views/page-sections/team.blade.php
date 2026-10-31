@php($alt = ! empty($sec['alt']) ? ' section--alt' : '')
@php($sid = ! empty($sec['id']) ? ' id="' . e($sec['id']) . '"' : '')
<section class="section{{ $alt }}"{!! $sid !!}><div class="container">
  <x-sec-head :eyebrow="$sec['eyebrow'] ?? null" :title="$sec['title'] ?? ''" :desc="$sec['desc'] ?? null" :center="! empty($sec['center'])" />
  <div class="team">
    @foreach (amol_team() as $m)<article class="member reveal"><div class="avatar">@if (! empty($m['photo']))<img src="{{ amol_asset('img/' . $m['photo']) }}" width="92" height="92" loading="lazy" alt="{{ $m['name'] }}">@else{{ $m['initials'] }}@endif</div><h3>{{ $m['name'] }}</h3><small>{{ $m['role'] }}</small><p>{{ $m['text'] }}</p></article>@endforeach
  </div>
</div></section>
