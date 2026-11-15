<aside id="map"></aside>

{{-- Flat panel over the map area that simply fades away to make page transition smooter. --}}
<div class="map-reveal" aria-hidden="true"></div>


{{-- From MapHintHelper ($mapHint); shell/MapHint.jsx is its React twin --}}
@if($mapHint)
    <div class="hint">
        <i class="fa-sharp {{ $mapHint['icon'] }}"></i>
        {{ $mapHint['text'] }}
    </div>
@endif
