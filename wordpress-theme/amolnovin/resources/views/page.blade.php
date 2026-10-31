@extends('layouts.app')

@section('content')
@while (have_posts()) @php(the_post())
@include('partials.page-hero')
@if (! empty($page['subnav']))
<nav class="subnav" aria-label="پیمایش صفحه"><div class="container subnav__in">@foreach (($page['sections'] ?? []) as $s)@if (! empty($s['id']) && ! empty($s['nav']))<a href="#{{ $s['id'] }}">{{ $s['nav'] }}</a>@endif @endforeach</div></nav>
@endif
@foreach (($page['sections'] ?? []) as $sec){!! amol_section($sec, $loop->iteration) !!}
@endforeach
@php($extra = trim((string) get_the_content()))
@if ($extra !== '')
<section class="section"><div class="container container--narrow"><div class="prose">{!! amol_content(get_the_content()) !!}</div></div></section>
@endif
@endwhile
@endsection
