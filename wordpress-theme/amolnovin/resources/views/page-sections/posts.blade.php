@php($alt = ! empty($sec['alt']) ? ' section--alt' : '')
@php($sid = ! empty($sec['id']) ? ' id="' . e($sec['id']) . '"' : '')
@php($by = \App\Content\Repo::postBy())
<section class="section{{ $alt }}"{!! $sid !!}><div class="container">
  <x-sec-head :eyebrow="$sec['eyebrow'] ?? null" :title="$sec['title'] ?? ''" :desc="$sec['desc'] ?? null" :center="! empty($sec['center'])" />
  <div class="posts">@foreach ($sec['slugs'] as $s)@if (isset($by[$s]))<x-post-card :p="$by[$s]" />@endif @endforeach</div>
</div></section>
