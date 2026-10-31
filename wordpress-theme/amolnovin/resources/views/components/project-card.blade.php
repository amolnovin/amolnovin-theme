@props(['p'])
@php($svc = amol_project_services())
@php($cats = amol_project_cats())
<article class="project reveal" data-cat="{{ $p['cat'] }}" data-services="{{ implode(' ', $p['services']) }}" data-search="{{ $p['title'] }} {{ $p['domain'] ?? '' }} {{ $p['card'] }}">
  <div class="project__media">
    <img src="{{ amol_img($p, 'portfolio') }}" width="960" height="600" loading="lazy" decoding="async" alt="نمونه‌کار طراحی سایت {{ $p['title'] }}">
    <div class="project__badges">@foreach ($p['services'] as $s)<span class="badge{{ $s === 'design' ? ' badge--teal' : '' }}">{{ $svc[$s] }}</span>@endforeach</div>
    @if (! empty($p['detail']))<span class="project__open">مطالعه‌ی موردی @icon('arrow-left')</span>@endif
  </div>
  <div class="project__body">
    <div class="project__meta"><span>{{ $cats[$p['cat']] }}</span>@if (! empty($p['domain']))<span dir="ltr">{{ $p['domain'] }}</span>@endif</div>
    <h3>@if (! empty($p['detail']))<a href="{{ amol_entity_url($p, 'project') }}">{{ $p['title'] }}</a>@else{{ $p['title'] }}@endif</h3>
    <p>{{ $p['card'] }}</p>
    <div class="project__foot">
      <div class="project__tech">@foreach (array_slice($p['tech'], 0, 3) as $t)<span>{{ $t }}</span>@endforeach</div>
      @if (! empty($p['url']))<a class="project__ext" href="{{ $p['url'] }}" target="_blank" rel="noopener">بازدید سایت @icon('external-link', 'i--sm')</a>@endif
    </div>
  </div>
</article>
