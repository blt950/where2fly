@extends('layouts.app')

@section('meta-description')
<meta name="description" content="Always struggling to decide where to fly? Find some suggested destinations with fun weather and coverage!">
@endsection

@section('resources')
@vite('resources/js/nouislider.js')
@vite('resources/js/multiselect.js')
@endsection

@section('content')

<div class="cover-container text-center d-flex w-100 h-100 p-3 mx-auto flex-column">
    
    <div>
        @include('layouts.menu')
    
        <main class="front">
            @include('front.parts.top')

            <form id="form" action="{{ route('search.routes') }}" method="POST">
                @csrf
                
                <div class="row g-3 justify-content-center">
                    <div class="col-xs-12 col-sm-12 col-md-3 col-lg-2 text-start">
                        <label for="departure">Departure (ICAO)</label>
                        <input type="text" class="form-control" id="departure" name="departure" required oninput="this.value = this.value.toUpperCase()" maxlength="4" value="{{ old('departure') }}">
                        @error('departure')
                        <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-xs-12 col-sm-12 col-md-3 col-lg-2 text-start">
                        <label for="arrival">Arrival (ICAO)</label>
                        <input type="text" class="form-control" id="arrival" name="arrival" required oninput="this.value = this.value.toUpperCase()" maxlength="4" value="{{ old('arrival') }}">
                        @error('arrival')
                        <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-xs-12 col-sm-12 col-md-3 col-lg-2 text-start">
                        <label for="sort">Sort by</label>
                        <select class="form-control" id="sort" name="sort">
                            <option value="flight" {{ old('sort') == "flights" ? "selected" : "" }}>Callsigns</option>
                            <option value="timestamp" {{ old('sort') == "timestamp" ? "selected" : "" }}>Last seen</option>
                        </select>
                        @error('sort')
                        <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                        @enderror
                    </div>
            
                    <div class="col-sm-12 col-md-9 col-lg-2 align-self-start">
                        <button type="submit" id="submitBtn" class="btn btn-primary text-uppercase">
                            Search <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            
                @error('airportNotFound')
                    <div class="validation-error mt-2">{{ $message }}</div>
                @enderror
                
            </form>

        </main>
    </div>
        
    @include('scripts.search')
    
    @include('layouts.footer')
</div>

@endsection