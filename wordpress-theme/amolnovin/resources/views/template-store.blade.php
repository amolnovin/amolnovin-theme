{{--
  Template Name: فروشگاه افزونه‌ها
--}}
@extends('layouts.app')

@section('content')
@php
  $products = \App\Content\Repo::products();
  $cats = \App\Content\Repo::storeCats();
@endphp
@include('partials.page-hero')
<section class="section" id="products" aria-labelledby="products-title">
  <div class="container">
    <x-sec-head eyebrow="افزونه‌ها" :title="count($products) . ' افزونه وردپرس و ووکامرس کاربردی'" desc="همه‌ی افزونه‌ها توسط تیم آمل نوین نوشته، آزمایش و پشتیبانی می‌شوند. سه افزونه بدون نیاز به لایسنس ارائه می‌شود." :center="false" id="products-title" />
    <div class="toolbar">
      <div class="filters" role="group" aria-label="فیلتر افزونه‌ها">
        @foreach ($cats as [$k, $l])<button class="filter-btn{{ $loop->first ? ' is-active' : '' }}" type="button" data-filter="{{ $k }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">{{ $l }}</button>@endforeach
        <span style="width:1px;background:var(--line-2);margin-inline:.3rem"></span>
        <button class="filter-btn" type="button" data-filter="free" aria-pressed="false">بدون لایسنس</button>
        <button class="filter-btn" type="button" data-filter="paid" aria-pressed="false">نیازمند لایسنس</button>
      </div>
      <div class="toolbar__search">@icon('search')<input type="search" data-filter-search placeholder="جست‌وجوی افزونه…" aria-label="جست‌وجوی افزونه"></div>
    </div>
    <p class="result-count">نمایش <strong data-filter-count>{{ count($products) }}</strong> افزونه</p>
    <div class="products" data-filter-group=".products .product">@foreach ($products as $pr)<x-product-card :pr="$pr" />@endforeach</div>
    <div class="empty-state"><p>افزونه‌ای با این مشخصات پیدا نشد. <a href="{{ amol_url('/services/coding/') }}">برای ساخت افزونه‌ی اختصاصی</a> با ما تماس بگیرید.</p></div>
  </div>
</section>
@foreach (($page['sections'] ?? []) as $sec){!! amol_section($sec, $loop->iteration) !!}@endforeach
@endsection
