<div class="map-card" data-map data-src="https://www.google.com/maps?q={{ rawurlencode($site['maps_query']) }}&amp;output=embed&amp;hl=fa">
  <div class="map-card__in">
    <span class="map-pin">@icon('map-pin')</span>
    <strong>{{ $site['address'] }}</strong>
    <p class="muted small" style="margin:0">دفتر آمل نوین، با هماهنگی قبلی</p>
    <div class="btn-row" style="justify-content:center"><button class="btn btn--primary btn--sm" type="button" data-map-load>@icon('map') نمایش نقشه</button><a class="btn btn--ghost btn--sm" href="https://www.google.com/maps/search/?api=1&amp;query={{ rawurlencode($site['maps_query']) }}" target="_blank" rel="noopener">مسیریابی @icon('external-link', 'i--sm')</a></div>
  </div>
</div>
