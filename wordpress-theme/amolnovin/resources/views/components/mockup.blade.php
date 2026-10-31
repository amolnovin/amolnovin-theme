@props(['slug', 'alt' => ''])
<img class="mock" src="{{ amol_asset('img/types/' . $slug . '.svg') }}" width="640" height="440" alt="{{ $alt }}" @if (! $alt) aria-hidden="true" @endif>
