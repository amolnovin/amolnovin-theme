@php($alt = ! empty($sec['alt']) ? ' section--alt' : '')
@php($sid = ! empty($sec['id']) ? ' id="' . e($sec['id']) . '"' : '')
@php($by = \App\Content\Repo::projectBy())
<section class="section{{ $alt }}"{!! $sid !!}><div class="container">
  <x-sec-head :eyebrow="$sec['eyebrow'] ?? null" :title="$sec['title'] ?? ''" :desc="$sec['desc'] ?? null" :center="! empty($sec['center'])" />
  <div class="projects">@foreach ($sec['slugs'] as $s)@if (isset($by[$s]))<x-project-card :p="$by[$s]" />@endif @endforeach</div>
  <p class="center mt-3"><a class="btn btn--ghost" href="{{ amol_url('/portfolio/') }}">مشاهده‌ی همه‌ی نمونه‌کارها @icon('arrow-left', 'i--arrow')</a></p>
</div></section>
