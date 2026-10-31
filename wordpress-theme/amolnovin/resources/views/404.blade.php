@extends('layouts.app')

@section('content')
<section class="err-page"><div class="container">
  <div class="err-page__code" aria-hidden="true">۴۰۴</div>
  <h1 style="font-size:clamp(1.6rem,1.2rem+1.6vw,2.4rem)">صفحه‌ی مورد نظر پیدا نشد</h1>
  <p class="lead" style="margin-inline:auto;max-width:560px">ممکن است آدرس تغییر کرده یا صفحه حذف شده باشد. از لینک‌های زیر ادامه دهید یا با ما تماس بگیرید.</p>
  <div class="btn-row mt-3" style="justify-content:center">
    <a class="btn btn--cta btn--lg" href="{{ home_url('/') }}">@icon('house') بازگشت به صفحه‌ی اصلی</a>
    <a class="btn btn--ghost btn--lg" href="{{ amol_url('/services/web-design/') }}">طراحی سایت</a>
    <a class="btn btn--ghost btn--lg" href="{{ amol_url('/contact-us/') }}">تماس با ما</a>
  </div>
  <p class="mt-3 muted small">یا از جست‌وجو استفاده کنید: <button class="btn btn--sm btn--ghost" type="button" data-search-open>@icon('search') جست‌وجو (Ctrl+K)</button></p>
</div></section>
@endsection
