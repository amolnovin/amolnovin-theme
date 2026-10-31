@php($crumbs = $crumbs ?? [])
<nav class="breadcrumb" aria-label="مسیر صفحه"><ol>
  <li><a href="{{ amol_url('/') }}">خانه</a></li>
  @foreach ($crumbs as $i => $crumb)@php([$label, $cpath] = $crumb)<li @if ($loop->last) aria-current="page" @endif>@if ($cpath && ! $loop->last)<a href="{{ amol_url($cpath) }}">{{ $label }}</a>@else{{ $label }}@endif</li>@endforeach
</ol></nav>
