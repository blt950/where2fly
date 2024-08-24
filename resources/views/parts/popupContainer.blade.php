<div class="popup-container">
    {{-- Let's draw all airport cards here --}}
    @isset($airportsMapCollection)
        @foreach($airportsMapCollection as $airport)
            @include('parts.mapCard', ['airport' => $airport])
        @endforeach
    @endisset
</div>