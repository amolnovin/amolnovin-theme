@php($alt = ! empty($sec['alt']) ? ' section--alt' : '')
@php($sid = ! empty($sec['id']) ? ' id="' . e($sec['id']) . '"' : '')
@php($hl = $sec['hl'] ?? null)
<section class="section{{ $alt }}"{!! $sid !!}><div class="container">
  <x-sec-head :eyebrow="$sec['eyebrow'] ?? null" :title="$sec['title'] ?? ''" :desc="$sec['desc'] ?? null" :center="! empty($sec['center'])" />
  <div class="table-wrap reveal"><table class="table">
    <thead><tr>@foreach ($sec['head'] as $h)<th scope="col" class="{{ $loop->index === $hl ? 'is-hl' : '' }}">{{ $h }}</th>@endforeach</tr></thead>
    <tbody>@foreach ($sec['rows'] as $r)<tr>@foreach ($r as $c)@if ($loop->first)<th scope="row">{!! amol_rl($c) !!}</th>@else<td class="{{ $loop->index === $hl ? 'is-hl' : '' }}">{!! amol_rl($c) !!}</td>@endif @endforeach</tr>@endforeach</tbody>
  </table></div>
  @if (! empty($sec['note']))<p class="small muted mt-2">{!! amol_rl($sec['note']) !!}</p>@endif
</div></section>
