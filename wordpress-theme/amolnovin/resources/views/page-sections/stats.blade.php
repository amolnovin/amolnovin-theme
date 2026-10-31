<section class="section section--tight{{ ! empty($sec['dark']) ? ' section--dark' : '' }}"><div class="container">
  <div class="stats">
    @foreach ($sec['items'] as $s)<div class="stat reveal"><span class="stat__num" data-count="{{ $s['n'] }}" data-suffix="{{ $s['suffix'] ?? '' }}">{{ $s['n'] }}{{ $s['suffix'] ?? '' }}</span><span class="stat__label">{{ $s['label'] }}</span>@if (! empty($s['small']))<small>{{ $s['small'] }}</small>@endif</div>@endforeach
  </div>
</div></section>
