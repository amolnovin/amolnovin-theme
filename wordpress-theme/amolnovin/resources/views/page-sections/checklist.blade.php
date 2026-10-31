@php($alt = ! empty($sec['alt']) ? ' section--alt' : '')
@php($sid = ! empty($sec['id']) ? ' id="' . e($sec['id']) . '"' : '')
<section class="section{{ $alt }}"{!! $sid !!}><div class="container">
  <x-sec-head :eyebrow="$sec['eyebrow'] ?? null" :title="$sec['title'] ?? ''" :desc="$sec['desc'] ?? null" :center="! empty($sec['center'])" />
  <div class="card card--feature reveal"><ul class="checklist checklist--cols">@foreach ($sec['items'] as $p)<li>@icon('check')<span>{!! amol_rl($p) !!}</span></li>@endforeach</ul></div>
</div></section>
