@php($alt = ! empty($sec['alt']) ? ' section--alt' : '')
@php($sid = ! empty($sec['id']) ? ' id="' . e($sec['id']) . '"' : '')
<section class="section{{ $alt }}"{!! $sid !!}><div class="container">
  <div class="split {{ amol_or($sec, 'cls', 'split--wide-left') }}">
    <div>
      <x-sec-head :eyebrow="$sec['eyebrow'] ?? null" :title="$sec['title'] ?? ''" />
      <div class="prose reveal">{!! amol_rl($sec['html'] ?? '') !!}</div>
      @if (! empty($sec['cta']))<div class="btn-row mt-3 reveal"><a class="btn btn--primary" href="{{ amol_url($sec['cta'][1]) }}">{{ $sec['cta'][0] }} @icon('arrow-left', 'i--arrow')</a>@if (! empty($sec['cta2']))<a class="btn btn--ghost" href="{{ amol_url($sec['cta2'][1]) }}">{{ $sec['cta2'][0] }}</a>@endif</div>@endif
    </div>
    <div class="split__media reveal">
      @if (! empty($sec['points']))<div class="card card--feature"><h3>{{ $sec['points_title'] ?? '' }}</h3><ul class="checklist">@foreach ($sec['points'] as $p)<li>@icon('check')<span>{{ $p }}</span></li>@endforeach</ul></div>@endif
      @if (! empty($sec['mock']))<x-mockup :slug="$sec['mock']" />@endif
      @if (! empty($sec['img']))<div class="img-frame"><img src="{{ amol_asset($sec['img']) }}" width="{{ amol_or($sec, 'img_w', 960) }}" height="{{ amol_or($sec, 'img_h', 600) }}" loading="lazy" alt="{{ $sec['img_alt'] ?? '' }}"></div>@endif
    </div>
  </div>
</div></section>
