<div class="popup-container" id="popup-container">
    {{-- Let's draw all airport cards here --}}
    @isset($airportsMapCollection)
        @foreach($airportsMapCollection as $airport)
            @include('parts.mapCard', ['airport' => $airport])
        @endforeach
    @endisset

    @isset($sceneriesCollection)
        @foreach($airports as $airport)
            @include('parts.sceneryCard', ['airport' => $airport, 'sceneries' => $sceneriesCollection])
        @endforeach
    @endisset
</div>