{{--
  «قطعه کد سفارشی» — renders one snippet of the Custom HTML Snippets plugin inside a page.
  Data editor → section list → «قطعه کد سفارشی»; the snippet itself is chosen in the theme settings panel
  or by its ID here.
--}}
@php($snippetId = (int) amol_or($sec, 'snippet', 0))
@php($snippetHtml = \App\Integrations\Snippets::render($snippetId))
@if ($snippetHtml !== '')
<section class="section{{ ! empty($sec['alt']) ? ' section--alt' : '' }}"{!! ! empty($sec['id']) ? ' id="' . e($sec['id']) . '"' : '' !!}>
  <div class="container">
    @if (($sec['title'] ?? '') !== '')
    <x-sec-head :eyebrow="$sec['eyebrow'] ?? ''" :title="$sec['title']" :desc="$sec['desc'] ?? null" :center="! empty($sec['center'])" />
    @endif
    <div class="snippet-embed">{!! $snippetHtml !!}</div>
  </div>
</section>
@endif
