@extends('layouts.app')

@section('content')
@include('partials.page-hero')
<section class="section"><div class="container">
  <div class="mb-3">{!! get_search_form(false) !!}</div>
  @if (have_posts())
  <div class="link-cards">
    @while (have_posts()) @php(the_post())
    <a class="link-card" href="{{ get_permalink() }}"><span><strong>{{ get_the_title() }}</strong><small>{{ wp_trim_words(wp_strip_all_tags(get_the_excerpt()), 24, '…') }}</small></span>@icon('arrow-left', 'i--go i--sm')</a>
    @endwhile
  </div>
  {!! get_the_posts_pagination(['mid_size' => 1, 'prev_text' => 'قبلی', 'next_text' => 'بعدی']) !!}
  @else
  <div class="callout">@icon('info')<p>نتیجه‌ای پیدا نشد؛ عبارت دیگری را امتحان کنید یا از <a href="{{ amol_url('/contact-us/') }}">فرم تماس</a> استفاده کنید.</p></div>
  @endif
</div></section>
@endsection
