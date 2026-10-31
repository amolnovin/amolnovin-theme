@extends('layouts.app')

@section('content')
@include('partials.page-hero')
<section class="section"><div class="container">
  @if (have_posts())
  <div class="posts">@while (have_posts()) @php(the_post())<x-post-card :p="\App\Content\Repo::entity(get_post())" />@endwhile</div>
  {!! get_the_posts_pagination(['mid_size' => 1, 'prev_text' => 'قبلی', 'next_text' => 'بعدی']) !!}
  @else
  <div class="empty-state" style="display:block"><p>مطلبی در این بخش پیدا نشد.</p></div>
  @endif
</div></section>
@endsection
