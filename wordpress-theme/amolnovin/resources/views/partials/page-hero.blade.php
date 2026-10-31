@php
  $hero = $page['hero'] ?? [];
  $visual = $hero['visual'] ?? null;
@endphp
<section class="page-hero">
  <div class="container">
    @include('partials.breadcrumb', ['crumbs' => $page['crumbs'] ?? []])
    <div class="page-hero__grid{{ $visual ? '' : ' page-hero__grid--solo' }}">
      <div>
        @if (! empty($page['eyebrow']))<span class="eyebrow">{{ $page['eyebrow'] }}</span>@endif
        <h1>{!! $page['h1'] !!}</h1>
        <p class="lead">{!! amol_rl($page['lead'] ?? '') !!}</p>
        @if (! empty($hero['chips']))<ul class="chip-row">@foreach ($hero['chips'] as $c)<li class="chip">{{ $c }}</li>@endforeach</ul>@endif
        @if (! empty($hero['cta']))<div class="btn-row">@foreach ($hero['cta'] as $c)<a class="btn {{ $c[2] ?? 'btn--cta' }} btn--lg" href="{{ (str_starts_with($c[1], '/') || str_starts_with($c[1], '#')) ? amol_url($c[1]) : $c[1] }}">{{ $c[0] }}</a>@endforeach</div>@endif
        @if (! empty($hero['meta']))<div class="page-hero__meta">@foreach ($hero['meta'] as [$ic, $tx])<span>@icon($ic) {{ $tx }}</span>@endforeach</div>@endif
      </div>
      @if ($visual)
      <div class="page-hero__visual">
        @if ($visual[0] === 'mock')<x-mockup :slug="$visual[1]" :alt="$visual[2] ?? ''" />
        @elseif ($visual[0] === 'img')<div class="img-frame"><img src="{{ amol_asset($visual[1]) }}" width="{{ $visual[3] }}" height="{{ $visual[4] }}" alt="{{ $visual[2] }}" fetchpriority="high"></div>
        @elseif ($visual[0] === 'html'){!! amol_rl($visual[1]) !!}@endif
      </div>
      @endif
    </div>
  </div>
</section>
@if (! empty($page['tldr']))
<section class="section section--tight" aria-label="خلاصه‌ی پاسخ">
  <div class="container container--narrow">
    <div class="tldr reveal"><span class="tldr__icon">@icon('sparkles')</span><div><strong class="tldr__title">خلاصه‌ی پاسخ</strong><p>{!! amol_rl($page['tldr']) !!}</p></div></div>
  </div>
</section>
@endif
