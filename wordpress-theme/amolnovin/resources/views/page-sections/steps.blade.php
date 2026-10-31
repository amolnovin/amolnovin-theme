@php($alt = ! empty($sec['alt']) ? ' section--alt' : '')
@php($sid = ! empty($sec['id']) ? ' id="' . e($sec['id']) . '"' : '')
<section class="section{{ $alt }}"{!! $sid !!}><div class="container">
  <x-sec-head :eyebrow="$sec['eyebrow'] ?? null" :title="$sec['title'] ?? ''" :desc="$sec['desc'] ?? null" :center="! empty($sec['center'])" />
  <ol class="steps steps--{{ count($sec['items']) }}">
    @foreach ($sec['items'] as $it)
    <li class="step reveal" style="--d:{{ ($loop->index % 6) * 0.06 }}s">
      <span class="step__num">{{ $loop->iteration }}</span>
      @if (! empty($it['time']))<span class="step__time">{{ $it['time'] }}</span>@endif
      <h3>{{ $it['title'] }}</h3><p>{{ $it['text'] }}</p>
    </li>
    @endforeach
  </ol>
</div></section>
