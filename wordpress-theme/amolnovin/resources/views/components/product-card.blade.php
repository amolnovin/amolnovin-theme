@props(['pr'])
@php($sc = amol_store_cats())
<article class="product reveal" data-cat="{{ $pr['cat'] }}" data-lic="{{ ! empty($pr['licensed']) ? 'paid' : 'free' }}" data-search="{{ $pr['name'] }} {{ $pr['desc'] }}">
  <div class="product__media">
    @if (! empty($pr['_thumb']))<img src="{{ $pr['_thumb'] }}" width="800" height="450" loading="lazy" decoding="async" alt="افزونه‌ی وردپرس {{ $pr['name'] }}">
    @elseif (! empty($pr['img']))<img src="{{ amol_asset('img/store/' . $pr['img'] . '.webp') }}" width="800" height="450" loading="lazy" decoding="async" alt="افزونه‌ی وردپرس {{ $pr['name'] }}">
    @else<div class="product__ph" style="--c1:{{ $pr['ph'][0] ?? '#272869' }};--c2:{{ $pr['ph'][1] ?? '#13a89e' }}" role="img" aria-label="{{ $pr['name'] }}">@icon($pr['icon'], 'i--xl')</div>@endif
  </div>
  <div class="product__body">
    <div class="flex between items-center"><span class="product__cat">@icon($pr['icon'], 'i--sm') {{ $sc[$pr['cat']] ?? '' }}</span><span class="lic {{ ! empty($pr['licensed']) ? 'lic--paid' : 'lic--free' }}">{{ ! empty($pr['licensed']) ? 'نیازمند لایسنس' : 'بدون نیاز به لایسنس' }}</span></div>
    <h3>{{ $pr['name'] }}</h3>
    <p>{{ $pr['desc'] }}</p>
    <div class="product__foot"><a class="btn btn--primary btn--sm" href="{{ \App\Content\Repo::storeUrl() }}{{ $pr['slug'] }}" target="_blank" rel="noopener">@icon('shopping-bag') مشاهده و خرید</a></div>
  </div>
</article>
