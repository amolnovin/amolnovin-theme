@extends('layouts.app')

@section('content')
@while (have_posts()) @php(the_post())
@php($p = $page['post'])
@php($postBy = \App\Content\Repo::postBy())
<section class="page-hero"><div class="container">
  @include('partials.breadcrumb', ['crumbs' => $page['crumbs']])
  <div class="page-hero__grid page-hero__grid--solo article-head">
    <div>
      <span class="eyebrow">{{ $p['cat'] }}</span>
      <h1>{{ $p['title'] }}</h1>
      @php($lead = \App\Content\Repo::lead($p))
      @if ($lead !== '')<p class="lead">{{ $lead }}</p>@endif
      <div class="page-hero__meta"><span>@icon('user') {{ $site['short'] }}</span><span>@icon('calendar-days') <time datetime="{{ $p['date'] }}">{{ $p['jdate'] }}</time></span><span>@icon('clock') {{ $p['read'] }} دقیقه مطالعه</span></div>
    </div>
  </div>
</div></section>
<section class="section" data-article>
  <div class="container">
    <div class="article-grid">
      <article>
        <div class="article-cover"><img src="{{ amol_img($p, 'blog') }}" width="900" height="506" alt="{{ $p['title'] }}" fetchpriority="high"></div>
        <nav class="toc toc--inline" aria-label="فهرست مطالب"><h2>@icon('list') فهرست مطالب</h2><ol data-toc></ol></nav>
        <div class="prose" data-toc-source>{!! amol_content($p['body']) !!}</div>
        @if (! empty($p['faq']))<div class="mt-4"><h2>سوالات متداول</h2><x-faq :items="$p['faq']" :open-first="true" /></div>@endif
        <div class="author-box"><span class="avatar">آ</span><div><strong>تیم محتوای {{ $site['short'] }}</strong><p>مجموعه‌ی فناوری اطلاعات آمل نوین از سال {{ $site['founded_j'] }} در حوزه‌ی طراحی سایت، سئو و پشتیبانی فعال است. <a href="{{ amol_url('/about-us/') }}">درباره‌ی ما</a></p></div></div>
        <div class="share mt-3"><strong class="small">اشتراک‌گذاری:</strong>
          <a class="icon-btn" href="https://t.me/share/url?url={{ rawurlencode($page['canonical']) }}&amp;text={{ rawurlencode($p['title']) }}" target="_blank" rel="noopener" aria-label="اشتراک در تلگرام">@icon('b-telegram')</a>
          <a class="icon-btn" href="https://wa.me/?text={{ rawurlencode($p['title'] . ' ' . $page['canonical']) }}" target="_blank" rel="noopener" aria-label="اشتراک در واتساپ">@icon('b-whatsapp')</a>
          <button class="icon-btn" type="button" data-copy aria-label="کپی لینک">@icon('copy')</button></div>
        @if (comments_open() || get_comments_number())@php(comments_template())@endif
      </article>
      <aside class="article-aside">
        <nav class="toc toc--side" aria-label="فهرست مطالب"><h2>@icon('list') فهرست مطالب</h2><ol data-toc></ol></nav>
        <div class="lead-card" style="padding:1.3rem"><h2 style="font-size:1.05rem">نیاز به مشاوره دارید؟</h2><p class="muted small">پاسخ در {{ $site['response_time'] }}.</p><x-lead-form id="f-post" service="" variant="compact" cta="ارسال درخواست" /></div>
      </aside>
    </div>
  </div>
</section>
<section class="section section--alt"><div class="container">
  <x-sec-head eyebrow="مقالات مرتبط" title="بخوانید و بیشتر بدانید" />
  <div class="posts">@foreach (($p['related'] ?? []) as $s)@if (isset($postBy[$s]))<x-post-card :p="$postBy[$s]" />@endif @endforeach</div>
</div></section>
<x-cta-band title="آماده‌اید این نکته‌ها را روی سایت خودتان اجرا کنیم؟" text="یک جلسه‌ی مشاوره‌ی رایگان بگیرید؛ نیاز شما را بررسی و پیشنهاد عملی ارائه می‌کنیم." :primary="['دریافت مشاوره‌ی رایگان', '/contact-us/']" :secondary="['هزینه طراحی سایت', '/price-wordpress/']" />
@endwhile
@endsection
