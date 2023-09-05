@extends('layouts.app')

@section('meta-description')
<meta name="description" content="Always struggling to decide where to fly? Find some suggested destinations with fun weather and coverage!">
@endsection

@section('resources')
@vite('resources/js/nouislider.js')
@vite('resources/js/multiselect.js')
@endsection

@section('title', 'Search')
@section('content')

<div class="cover-container text-center d-flex w-100 h-100 p-3 mx-auto flex-column">
    
    <div>
        @include('layouts.menu')
    
        <main class="front">
            <h1 class="mb-0 mt-5">What kind of flight do you want?</h1>
            <p class="front mb-5">Find destinations based on your weather or coverage criteria</p>
            
            <form id="form" action="{{ route('search') }}" method="POST">
                @csrf
                
                <div class="row g-3 justify-content-center">
                    <div class="col-xs-12 col-sm-12 col-md-3 col-lg-2 text-start">
                        <label for="departure">Departure (ICAO)</label>
                        <input type="text" class="form-control" id="departure" name="departure" placeholder="Random" oninput="this.value = this.value.toUpperCase()" maxlength="4" value="{{ old('departure') }}">
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
                    
                    <div class="col-xs-12 col-sm-12 col-md-3 col-lg-2 text-start">
                        <label for="codeletter">Arrival Aircraft Code</label>
                        <select class="form-control" id="codeletter" name="codeletter">
                            <option disabled selected>Choose</option>
                            <option value="A" {{ old('codeletter') == "A" ? "selected" : "" }}>A (PIPER/CESSNA)</option>
                            <option value="B" {{ old('codeletter') == "B" ? "selected" : "" }}>B (CRJ/DHC)</option>
                            <option value="C" {{ old('codeletter') == "C" ? "selected" : "" }}>C (737/A320/ERJ)</option>
                            <option value="D" {{ old('codeletter') == "D" ? "selected" : "" }}>D (B767/A310)</option>
                            <option value="E" {{ old('codeletter') == "E" ? "selected" : "" }}>E (B777/B787/A330)</option>
                            <option value="F" {{ old('codeletter') == "F" ? "selected" : "" }}>F (747-8/A380)</option>
                        </select>
                        @error('codeletter')
                        <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-2 text-start">
                        <label>Intended Air Time</label>
                        <input type="hidden" id="airtimeMin" name="airtimeMin" value="0">
                        <input type="hidden" id="airtimeMax" name="airtimeMax" value="12">
                        <div id="slider-airtime" class="mt-1 mb-1"></div>
                        <span id="slider-airtime-text">0-12 hours</span>
                    </div>
                    
                    <div class="col-xs-12 col-sm-12 col-md-3 col-lg-2 text-start">
                        <label>Order by</label>
                        
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

                    <div class="col-sm-12 col-md-9 col-lg-2 align-self-start">
                        <button type="submit" id="submitBtn" class="btn btn-primary text-uppercase">
                            Search <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <div id="filters" class="hide-filters">             
                    <div class="row g-3 mt-3 pb-4 justify-content-center bt">
                        
                        <div class="col-sm-6 col-md-4 col-lg-4 text-start">
                            <label>Weather parameters</label>

                            @foreach(\App\Http\Controllers\ScoreController::$score_types as $k => $s)
                            @if(str_starts_with($k, 'METAR'))
                                <div class="mt-1">
                                    <div class="btn-group" role="group" aria-label="Basic radio toggle button group">
                                        <input type="radio" class="btn-check red" name="{{ $k }}_filter" value="-1" id="{{ $k }}_exclude" autocomplete="off">
                                        <label class="btn btn-sm btn-dark btn-filter-width" for="{{ $k }}_exclude">
                                            <i class="fa-solid fa-xmark"></i>
                                        </label>
                                    
                                        <input type="radio" class="btn-check light" name="{{ $k }}_filter" value="0" id="{{ $k }}_neutral" autocomplete="off" checked>
                                        <label class="btn btn-sm btn-dark btn-filter-width" for="{{ $k }}_neutral">
                                            <i class="fa-solid fa-slash-forward"></i>
                                        </label>
                                    
                                        <input type="radio" class="btn-check green" name="{{ $k }}_filter" value="1" id="{{ $k }}_include" autocomplete="off">
                                        <label class="btn btn-sm btn-dark btn-filter-width" for="{{ $k }}_include">
                                            <i class="fa-solid fa-check"></i>
                                        </label>
                                    </div>
                                    <i class="ms-2 fa {{ $s['icon'] }}"></i>&nbsp;{{ $s['desc'] }}
                                </div>
                            @endif
                            @endforeach
                        </div>
                        
                        <div class="col-sm-6 col-md-5 col-lg-5 text-start">

                            <label>Meteo Condition</label>
                            <div>
                                <div class="btn-group" role="group" aria-label="Basic radio toggle button group">
                                    <input type="radio" class="btn-check light" name="metcondition" value="ANY" id="metcondition_all" autocomplete="off" checked>
                                    <label class="btn btn-sm btn-dark btn-filter-width-meteo" for="metcondition_all">
                                        Any
                                    </label>
                                
                                    <input type="radio" class="btn-check red" name="metcondition" value="IFR" id="metcondition_ifr" autocomplete="off">
                                    <label class="btn btn-sm btn-dark btn-filter-width-meteo" for="metcondition_ifr">
                                        IFR
                                    </label>
                                
                                    <input type="radio" class="btn-check green" name="metcondition" value="VFR" id="metcondition_vfr" autocomplete="off">
                                    <label class="btn btn-sm btn-dark btn-filter-width-meteo" for="metcondition_vfr">
                                        VFR
                                    </label>
                                </div>
                            </div>

                            <label class="pt-4">Network parameters</label>

                            @foreach(\App\Http\Controllers\ScoreController::$score_types as $k => $s)
                            @if(str_starts_with($k, 'VATSIM'))
                                <div class="mt-1">
                                    <div class="btn-group" role="group" aria-label="Basic radio toggle button group">
                                        <input type="radio" class="btn-check red" name="{{ $k }}_filter" value="-1" id="{{ $k }}_exclude" autocomplete="off">
                                        <label class="btn btn-sm btn-dark btn-filter-width" for="{{ $k }}_exclude">
                                            <i class="fa-solid fa-xmark"></i>
                                        </label>
                                    
                                        <input type="radio" class="btn-check light" name="{{ $k }}_filter" value="0" id="{{ $k }}_neutral" autocomplete="off" checked>
                                        <label class="btn btn-sm btn-dark btn-filter-width" for="{{ $k }}_neutral">
                                            <i class="fa-solid fa-slash-forward"></i>
                                        </label>
                                    
                                        <input type="radio" class="btn-check green" name="{{ $k }}_filter" value="1" id="{{ $k }}_include" autocomplete="off">
                                        <label class="btn btn-sm btn-dark btn-filter-width" for="{{ $k }}_include">
                                            <i class="fa-solid fa-check"></i>
                                        </label>
                                    </div>
                                    <i class="ms-2 fa {{ $s['icon'] }}"></i>&nbsp;{{ $s['desc'] }}
                                </div>
                            @endif
                            @endforeach

                            <label class="pt-4">Destination parameters</label>

                            <div class="mt-1">
                                <div class="btn-group" role="group" aria-label="Basic radio toggle button group">
                                    <input type="radio" class="btn-check red" name="airportWithRoutesOnly" value="-1" id="airportWithRoutesOnly_exclude" autocomplete="off">
                                    <label class="btn btn-sm btn-dark btn-filter-width" for="airportWithRoutesOnly_exclude">
                                        <i class="fa-solid fa-xmark"></i>
                                    </label>
                                
                                    <input type="radio" class="btn-check light" name="airportWithRoutesOnly" value="0" id="airportWithRoutesOnly_neutral" autocomplete="off" checked>
                                    <label class="btn btn-sm btn-dark btn-filter-width" for="airportWithRoutesOnly_neutral">
                                        <i class="fa-solid fa-slash-forward"></i>
                                    </label>
                                
                                    <input type="radio" class="btn-check green" name="airportWithRoutesOnly" value="1" id="airportWithRoutesOnly_include" autocomplete="off">
                                    <label class="btn btn-sm btn-dark btn-filter-width" for="airportWithRoutesOnly_include">
                                        <i class="fa-solid fa-check"></i>
                                    </label>
                                </div>
                                <i class="ms-2 fa fa-route"></i>&nbsp;With routes only
                            </div>

                            <div class="mt-1">
                                <div class="btn-group" role="group" aria-label="Basic radio toggle button group">
                                    <input type="radio" class="btn-check red" name="airportRunwayLights" value="-1" id="airportRunwayLights_exclude" autocomplete="off">
                                    <label class="btn btn-sm btn-dark btn-filter-width" for="airportRunwayLights_exclude">
                                        <i class="fa-solid fa-xmark"></i>
                                    </label>
                                
                                    <input type="radio" class="btn-check light" name="airportRunwayLights" value="0" id="airportRunwayLights_neutral" autocomplete="off" checked>
                                    <label class="btn btn-sm btn-dark btn-filter-width" for="airportRunwayLights_neutral">
                                        <i class="fa-solid fa-slash-forward"></i>
                                    </label>
                                
                                    <input type="radio" class="btn-check green" name="airportRunwayLights" value="1" id="airportRunwayLights_include" autocomplete="off">
                                    <label class="btn btn-sm btn-dark btn-filter-width" for="airportRunwayLights_include">
                                        <i class="fa-solid fa-check"></i>
                                    </label>
                                </div>
                                <i class="ms-2 fa fa-lightbulb-on"></i>&nbsp;Runway with lights
                            </div>

                            <div class="mt-1">
                                <div class="btn-group" role="group" aria-label="Basic radio toggle button group">
                                    <input type="radio" class="btn-check red" name="airportAirbases" value="-1" id="airportAirbases_exclude" autocomplete="off">
                                    <label class="btn btn-sm btn-dark btn-filter-width" for="airportAirbases_exclude">
                                        <i class="fa-solid fa-xmark"></i>
                                    </label>
                                
                                    <input type="radio" class="btn-check light" name="airportAirbases" value="0" id="airportAirbases_neutral" autocomplete="off" checked>
                                    <label class="btn btn-sm btn-dark btn-filter-width" for="airportAirbases_neutral">
                                        <i class="fa-solid fa-slash-forward"></i>
                                    </label>
                                
                                    <input type="radio" class="btn-check green" name="airportAirbases" value="1" id="airportAirbases_include" autocomplete="off">
                                    <label class="btn btn-sm btn-dark btn-filter-width" for="airportAirbases_include">
                                        <i class="fa-solid fa-check"></i>
                                    </label>
                                </div>
                                <i class="ms-2 fa fa-jet-fighter"></i>&nbsp;Airbases
                            </div>
                            
                        </div>
                        
                        <div class="col-sm-12 col-md-9 col-lg-3 text-start">

                            <label class="d-block">Airport Size</label>
                            <div>
                                <div class="form-check form-check-inline mb-0 me-reduced">
                                    <input class="form-check-input" type="checkbox" value="small_airport" id="filterAirportSizeSmall" name="filterAirportSize[]" checked>
                                    <label class="form-check-label" for="filterAirportSizeSmall">
                                        Small
                                    </label>
                                </div>
                                <div class="form-check form-check-inline mb-0 me-reduced">
                                    <input class="form-check-input" type="checkbox" value="medium_airport" id="filterAirportSizeMedium" name="filterAirportSize[]" checked>
                                    <label class="form-check-label" for="filterAirportSizeMedium">
                                        Medium
                                    </label>
                                </div>
                                <div class="form-check form-check-inline mb-0 me-reduced">
                                    <input class="form-check-input" type="checkbox" value="large_airport" id="filterAirportSizeLarge" name="filterAirportSize[]" checked>
                                    <label class="form-check-label" for="filterAirportSizeLarge">
                                        Large
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="pt-4">Arrival Elevation</label>
                                <input type="hidden" id="elevationMin" name="elevationMin" value="0">
                                <input type="hidden" id="elevationMax" name="elevationMax" value="18000">
                                <div id="slider-elevation" class="mt-1 mb-1"></div>
                                <span id="slider-elevation-text">0-18000ft</span>
                            </div>

                            <div>
                                <label class="pt-4">Arrival Runway Length</label>
                                <input type="hidden" id="rwyLengthMin" name="rwyLengthMin" value="0">
                                <input type="hidden" id="rwyLengthMax" name="rwyLengthMax" value="17000">
                                <div id="slider-rwy" class="mt-1 mb-1"></div>
                                <span id="slider-rwy-text">0-1000'</span>
                            </div>

                            <label class="pt-4">Airlines</label>
                            <select multiple 
                                multiselect-search="true" 
                                multiselect-select-all="true"
                                multiselect-max-items="1"
                                multiselect-hide-x="false"
                                name="airlines[]"
                                placeholder="All airlines">
                                {{ $airlines = \App\Models\Airline::orderBy('name')->has('flights')->get() }}
                                @foreach($airlines as $airline)
                                    <option value="{{ $airline->id }}">{{ $airline->name }}</option>
                                @endforeach
                            </select>

                        </div>
                    </div>
                </div>
                
                <div class="row g-3 mt-3 justify-content-center">
                    <div class="col-sm-12 align-self-end mb-3"> 
                        <div class="expandFilterGroup">
                            <div class="divider"></div>
                            <button type="button" id="expandFilters" class="button">Show more filters</button>
                            <div class="divider"></div>
                        </div>
                    </div>
                </div>
                
            </form>
        </main>
    </div>
    
    
    @include('scripts.search')
    
    <script>
        // Run scripts when DOM is loaded
        document.addEventListener('DOMContentLoaded', function () {

            const userLocale = 'de-DE';

            // Filter button
            document.getElementById('expandFilters').addEventListener('click', function () {
                var filter = document.getElementById('filters');
                filter.classList.toggle('hide-filters');

                if (filter.classList.contains('hide-filters')) {
                    document.getElementById('expandFilters').innerHTML = 'Show more filters';
                } else {
                    document.getElementById('expandFilters').innerHTML = 'Hide filters';
                }
            });

            // Sliders
            var elevationSlider = document.getElementById('slider-elevation');
            noUiSlider.create(elevationSlider, {
                start: [{{ old('elevationMin') ? old('elevationMin') : -2000 }}, {{ old('elevationMax') ? old('elevationMax') : 18000 }}],
                step: 1000,
                connect: true,
                behaviour: 'drag',
                range: {
                    'min': [-2000],
                    'max': [18000]
                }
            });
            
            var elevationSliderText = document.getElementById('slider-elevation-text');
            var elevationMinInput = document.getElementById('elevationMin');
            var elevationMaxInput = document.getElementById('elevationMax');
            elevationSlider.noUiSlider.on('update', function (values) {
                elevationSliderText.innerHTML = Math.round(values[0]).toLocaleString(userLocale) + '-' + Math.round(values[1]).toLocaleString(userLocale) + 'ft';
                elevationMinInput.value = Math.round(values[0])
                elevationMaxInput.value = Math.round(values[1])
            });
            
            var rwySlider = document.getElementById('slider-rwy');
            noUiSlider.create(rwySlider, {
                start: [{{ old('rwyLengthMin') ? old('rwyLengthMin') : 0 }}, {{ old('rwyLengthMax') ? old('rwyLengthMax') : 17000 }}],
                step: 500,
                connect: true,
                behaviour: 'drag',
                range: {
                    'min': [0],
                    'max': [17000]
                }
            });
            
            var rwySliderText = document.getElementById('slider-rwy-text');
            var rwyMinInput = document.getElementById('rwyLengthMin');
            var rwyMaxInput = document.getElementById('rwyLengthMax');
            rwySlider.noUiSlider.on('update', function (values) {
                rwySliderText.innerHTML = Math.round(values[0]).toLocaleString(userLocale) + '-' + Math.round(values[1]).toLocaleString(userLocale) + 'ft <span class="text-white text-opacity-50"> | ' + Math.round(values[0]/3.2808).toLocaleString(userLocale) + '-' + Math.round(values[1]/3.2808).toLocaleString(userLocale) + 'm</span>';
                rwyMinInput.value = Math.round(values[0])
                rwyMaxInput.value = Math.round(values[1])
            });
            
            var airtimeSlider = document.getElementById('slider-airtime');
            noUiSlider.create(airtimeSlider, {
                start: [{{ old('airtimeMin') ? old('airtimeMin') : 0 }}, {{ old('airtimeMax') ? old('airtimeMax') : 5 }}],
                step: 1,
                connect: true,
                behaviour: 'drag',
                range: {
                    'min': [0],
                    'max': [12]
                }
            });
            
            var airtimeSliderText = document.getElementById('slider-airtime-text');
            var airtimeMinInput = document.getElementById('airtimeMin');
            var airtimeMaxInput = document.getElementById('airtimeMax');
            airtimeSlider.noUiSlider.on('update', function (values) {

                if(values[1] == 12){
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