@props(['t'])
<a class="type-tile reveal" href="{{ amol_entity_url($t, 'type') }}">
  <span class="type-tile__ico" style="--c:{{ $t['color'] }}">@icon($t['icon'])</span>
  <span><strong>{{ $t['title'] }}</strong><small>{{ $t['short'] }}</small></span>
  @icon('arrow-left', 'i--go i--sm')
</a>
