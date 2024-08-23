@extends('layouts.app')

@section('meta-description')
    <meta name="description" content="Always struggling to decide where to fly? Find some suggested destinations with fun weather and coverage!">
@endsection

@section('resources')
    @vite('resources/js/nouislider.js')
    @vite('resources/js/multiselect.js')
@endsection

@section('content')

    @include('layouts.title', ['title' => 'Search for your flight', 'subtitle' => 'Find destinations based on your weather or coverage criteria'])

    <div class="container">
        @include('front.parts.tabs')

        <form id="form" action="{{ route('search.routes') }}" method="POST">
            @csrf
            
            <div class="row g-3 justify-content-center">
                <div class="col-xs-12 text-start">
                    <label for="departure">Departure (ICAO)</label>
                    <input type="text" class="form-control" id="departure" name="departure" required oninput="this.value = this.value.toUpperCase()" maxlength="4" value="{{ old('departure') }}">
                    @error('departure')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>

                <div class="col-xs-12 text-start">
                    <label for="arrival">Arrival (ICAO)</label>
                    <input type="text" class="form-control" id="arrival" name="arrival" required oninput="this.value = this.value.toUpperCase()" maxlength="4" value="{{ old('arrival') }}">
                    @error('arrival')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>

                <div class="col-xs-12 text-start">
                    <label for="sort">Sort by</label>
                    <select class="form-control" id="sort" name="sort">
                        <option value="flight" {{ old('sort') == "flights" ? "selected" : "" }}>Callsigns</option>
                        <option value="timestamp" {{ old('sort') == "timestamp" ? "selected" : "" }}>Last seen</option>
                    </select>
                    @error('sort')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>
        
                <div class="col-sm-12 align-self-start">
                    <button type="submit" id="submitBtn" class="btn btn-primary text-uppercase">
                        Search <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        
            @error('routeNotFound')
                <div class="validation-error mt-2">{{ $message }}</div>
            @enderror
            
        </form>
    </div>

    @isset($airportsMapCollection)
        @include('parts.popupContainer', ['airportsMapCollection' => ($airportsMapCollection)])
    @endisset
@endsection

@section('js')
    @vite('resources/js/functions/searchForm.js')
    @vite('resources/js/cards.js')
    @vite('resources/js/map.js')
    @include('scripts.defaultMap')
@endsection