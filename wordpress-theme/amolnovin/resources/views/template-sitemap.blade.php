{{--
  Template Name: نقشه‌ی سایت
--}}
@extends('layouts.app')

@section('content')
@include('partials.page-hero')
<section class="section"><div class="container"><div class="sitemap-cols">
  @foreach (amol_sitemap_groups($page) as [$title, $links])
  <section><h2>{{ $title }}</h2><ul class="bullets">@foreach ($links as [$t, $u])<li><a href="{{ amol_url($u) }}">{{ $t }}</a></li>@endforeach</ul></section>
  @endforeach
</div></div></section>
@endsection
