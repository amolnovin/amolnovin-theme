<section class="section section--tight{{ ! empty($sec['alt']) ? ' section--alt' : '' }}"><div class="container"><div class="facts-grid">
  @foreach ($sec['items'] as [$ic, $lb, $vl])<div class="fact reveal"><small>@icon($ic, 'i--sm') {{ $lb }}</small><strong>{{ $vl }}</strong></div>@endforeach
</div></div></section>
