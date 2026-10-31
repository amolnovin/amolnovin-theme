@props(['eyebrow' => null, 'title' => '', 'desc' => null, 'center' => false, 'id' => null, 'tag' => 'h2'])
<header class="sec-head{{ $center ? ' sec-head--center' : '' }} reveal">
  @if ($eyebrow)<span class="eyebrow">{{ $eyebrow }}</span>@endif
  <{{ $tag }}@if ($id) id="{{ $id }}"@endif>{!! $title !!}</{{ $tag }}>
  @if ($desc)<p>{!! amol_rl($desc) !!}</p>@endif
</header>
