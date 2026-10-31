@props(['p'])
<article class="post-card reveal">
  <div class="post-card__media"><img src="{{ amol_img($p, 'blog') }}" width="900" height="506" loading="lazy" decoding="async" alt="{{ $p['title'] }}"></div>
  <div class="post-card__body">
    <span class="chip chip--accent" style="align-self:flex-start">{{ $p['cat'] }}</span>
    <h3><a href="{{ amol_entity_url($p, 'post') }}">{{ $p['title'] }}</a></h3>
    <p>{{ $p['excerpt'] }}</p>
    <div class="post-meta"><span>@icon('calendar-days', 'i--sm') <time datetime="{{ $p['date'] }}">{{ $p['jdate'] }}</time></span><span>@icon('clock', 'i--sm') {{ $p['read'] }} دقیقه مطالعه</span></div>
  </div>
</article>
