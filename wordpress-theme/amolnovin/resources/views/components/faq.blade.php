@props(['items' => [], 'openFirst' => false])
<div class="faq">
@foreach ($items as [$q, $a])
  <details class="faq__item reveal"@if ($openFirst && $loop->first) open @endif>
    <summary><h3>{{ $q }}</h3><span class="faq__icon">@icon('plus')</span></summary>
    <div class="faq__body"><p>{!! amol_rl($a) !!}</p></div>
  </details>
@endforeach
</div>
