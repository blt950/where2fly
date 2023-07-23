@extends('layouts.app')

@section('resources')
@vite('resources/js/nouislider.js')
@endsection

@section('title', 'Search')
@section('content')

<div class="cover-container text-center d-flex w-100 h-100 p-3 mx-auto flex-column">
    
    @include('layouts.menu')
    
    <main>
        <h1 class="mb-0 mt-5">What kind of flight do you want?</h1>
        <p class="front mb-5">Find destinations based on your weather or coverage criteria</p>
        
        <form id="form" action="{{ route('search') }}" method="POST">
            @csrf
            
            <div class="row g-3 justify-content-center">
                <div class="col-xs-12 col-sm-12 col-md-3 col-lg-2 text-start">
                    <label for="departure">Departure</label>
                    <input type="text" class="form-control" id="departure" name="departure" placeholder="ICAO or blank" oninput="this.value = this.value.toUpperCase()" maxlength="4" value="{{ old('departure') }}">
                    @error('departure')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-xs-12 col-sm-12 col-md-3 col-lg-2 text-start">
                    <label for="continent">Destination Area</label>
                    <select class="form-control" id="continent" name="continent">
                        <option disabled selected>Choose</option>
                        <option value="DO" {{ old('continent') == "DO" ? "selected" : "" }}>Domestic Only</option>
                        <option value="AF" {{ old('continent') == "AF" ? "selected" : "" }}>Africa</option>
                        <option value="AS" {{ old('continent') == "AS" ? "selected" : "" }}>Asia</option>
                        <option value="EU" {{ old('continent') == "EU" ? "selected" : "" }}>Europe</option>
                        <option value="NA" {{ old('continent') == "NA" ? "selected" : "" }}>North America</option>
                        <option value="OC" {{ old('continent') == "OC" ? "selected" : "" }}>Oceania</option>
                        <option value="SA" {{ old('continent') == "SA" ? "selected" : "" }}>South America</option>
                    </select>
                    @error('continent')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3 text-start">
                    <label for="codeletter">Arrival Aircraft Code</label>
                    <select class="form-control" id="codeletter" name="codeletter">
                        <option disabled selected>Choose</option>
                        <option value="A" {{ old('codeletter') == "A" ? "selected" : "" }}>A (PIPER/CESSNA)</option>
                        <option value="B" {{ old('codeletter') == "B" ? "selected" : "" }}>B (CRJ/DHC)</option>
                        <option value="C" {{ old('codeletter') == "C" ? "selected" : "" }}>C (737-700/A320/ERJ)</option>
                        <option value="D" {{ old('codeletter') == "D" ? "selected" : "" }}>D (B767/A310)</option>
                        <option value="E" {{ old('codeletter') == "E" ? "selected" : "" }}>E (B777/B787/A330)</option>
                        <option value="F" {{ old('codeletter') == "F" ? "selected" : "" }}>F (747-8/A380)</option>
                    </select>
                    @error('codeletter')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-xs-12 col-sm-12 col-md-4 col-lg-2 text-start">
                    <label>Intended Air Time</label>
                    <input type="hidden" id="airtimeMin" name="airtimeMin" value="0">
                    <input type="hidden" id="airtimeMax" name="airtimeMax" value="4">
                    <div id="slider-airtime" class="mt-1 mb-1"></div>
                    <span id="slider-airtime-text">0-4 hours</span>
                </div>
                
                <div class="col-xs-12 col-sm-12 col-md-3 col-lg-2 text-start">
                    <label>Rank by</label>
                    
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" value="1" id="filterWeather" name="filterWeather" checked>
                        <label class="form-check-label" for="filterWeather">
                            Worst Weather
                        </label>
                        @error('filterWeather')
                        <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" value="1" id="filterATC" name="filterATC" checked>
                        <label class="form-check-label" for="filterATC">
                            ATC Coverage
                        </label>
                        @error('filterATC')
                        <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="row g-3 mt-1 justify-content-center">
                <div class="col-sm-12 align-self-end">
                    <button type="submit" id="submitBtn" class="btn btn-primary text-uppercase">
                        Find destination
                    </button>
                </div>
                <div class="col-sm-12 align-self-end mb-5">
                    <a class="text-primary" href="{{ route('front.advanced') }}">Advanced Search</a>
                </div>
            </div>
            
        </form>
    </main>
    
    
    @include('scripts.search')
    
    <script>
        // Run scripts when DOM is loaded
        document.addEventListener('DOMContentLoaded', function () {
            var airtimeSlider = document.getElementById('slider-airtime');
            noUiSlider.create(airtimeSlider, {
                start: [{{ old('airtimeMin') ? old('airtimeMin') : 0 }}, {{ old('airtimeMax') ? old('airtimeMax') : 4 }}],
                step: 1,
                connect: true,
                behaviour: 'drag',
                range: {
                    'min': [0],
                    'max': [8]
                }
            });
            
            var airtimeSliderText = document.getElementById('slider-airtime-text');
            var airtimeMinInput = document.getElementById('airtimeMin');
            var airtimeMaxInput = document.getElementById('airtimeMax');
            airtimeSlider.noUiSlider.on('update', function (values) {
                if(values[1] == 8){
                    airtimeSliderText.innerHTML = Math.round(values[0]) + '-' + Math.round(values[1]) + '+ hours';
                } else {
                    airtimeSliderText.innerHTML = Math.round(values[0]) + '-' + Math.round(values[1]) + ' hours';
                }
                
                airtimeMinInput.value = Math.round(values[0])
                airtimeMaxInput.value = Math.round(values[1])
            });
        }, false);
    </script>
    
    @include('layouts.footer')
</div>

@endsection