<form id="form" action="{{ route('search') }}" method="POST">
    @csrf
    
    <div class="row g-3 justify-content-center">
        <div class="col-xs-12 text-start">
            <label for="icao">{{ ucfirst($icao) }} (ICAO)</label>
            <input type="text" class="form-control" id="icao" name="icao" placeholder="Random" oninput="this.value = this.value.toUpperCase()" maxlength="4" value="{{ isset($prefilledIcao) ? $prefilledIcao : old('icao') }}">
            <input type="hidden" name="direction" value="{{ $icao }}">
            @error('icao')
            <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
            @enderror
        </div>
        
        <div class="col-xs-12 text-start">

            <label for="destination">
                {{ ucfirst($area) }} Area
            </label>
            <u-tags id="destination" data-input-name="destinations[]">
                <input list="destination-list" placeholder="Anywhere">
                <u-datalist id="destination-list" class="taller">
                    <u-option value="Anywhere">Anywhere</u-option>
                    <u-option value="Domestic">Domestic Only</u-option>
                    
                    <div class="divider">Continents</div>
                    <u-option value="C-AF">Africa</u-option>
                    <u-option value="C-AS">Asia</u-option>
                    <u-option value="C-EU">Europe</u-option>
                    <u-option value="C-NA">North America</u-option>
                    <u-option value="C-OC">Oceania</u-option>
                    <u-option value="C-SA">South America</u-option>
                    
                    <div class="divider">Countries</div>
                    @foreach($countries as $country_iso => $country)
                        <u-option value="{{ $country_iso }}">{{ $country }}</u-option>
                    @endforeach

                    <div class="divider">US States</div>
                    @foreach($usStates as $stateCode => $state)
                        <u-option value="US-{{ $stateCode }}">{{ $state }}</u-option>
                    @endforeach

                </u-datalist>
            </u-tags>
            @error('destinations')
            <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
            @enderror
        </div>
        
        <div class="col-xs-12 text-start">
            <label for="codeletter">Aircraft Code Letter <i class="fas fa-circle-question" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Select the aircraft closest to what you want to fly. This is used to calculate airtime and find compatible airports"></i></label>
            <select class="form-control" id="codeletter" name="codeletter">
                <option disabled selected>Choose</option>
                <option value="A" {{ old('codeletter') == "A" ? "selected" : "" }}>A - PIPER/CESSNA etc.</option>
                <option value="B" {{ old('codeletter') == "B" ? "selected" : "" }}>B - CRJ/DHC etc.</option>
                <option value="C" {{ old('codeletter') == "C" ? "selected" : "" }}>C - 737/A320/ERJ etc.</option>
                <option value="D" {{ old('codeletter') == "D" ? "selected" : "" }}>D - B767/A310 etc.</option>
                <option value="E" {{ old('codeletter') == "E" ? "selected" : "" }}>E - B777/B787/A330 etc.</option>
                <option value="F" {{ old('codeletter') == "F" ? "selected" : "" }}>F - 747-8/A380 etc.</option>
            </select>
            @error('codeletter')
            <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
            @enderror
        </div>
        
        <div class="col-xs-12 text-start">
            <label>Intended Air Time</label>
            <input type="hidden" id="airtimeMin" name="airtimeMin" value="0">
            <input type="hidden" id="airtimeMax" name="airtimeMax" value="12">
            <div id="slider-airtime" class="mt-1 mb-1"></div>
            <span id="slider-airtime-text">0-12 hours</span>
        </div>
        
        <div class="col-xs-12 text-start">
            <label for="whitelist">
                Whitelist
            </label>
            <u-tags id="whitelist" data-input-name="whitelists[]">
                <input list="whitelist-list" placeholder="Restrict your search">
                <u-datalist id="whitelist-list">
                    @foreach($lists as $list)
                        <u-option value="{{ $list->id }}">{{ $list->name }}</u-option>
                    @endforeach
                </u-datalist>
            </u-tags>
        </div>
    
        <div class="col-xs-12 text-start">
            <label>Order by</label>
            
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" value="1" id="sortByWeather" name="sortByWeather" checked>
                <label class="form-check-label" for="sortByWeather">
                    Worst Weather
                </label>
                @error('sortByWeather')
                <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" value="1" id="sortByATC" name="sortByATC" checked>
                <label class="form-check-label" for="sortByATC">
                    ATC Coverage
                </label>
                @error('sortByATC')
                <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>
        </div>
        
        <div class="col-sm-12 align-self-start">
            <button type="submit" class="submitBtn btn btn-primary text-uppercase">
                Search <i class="fas fa-search"></i>
            </button>
        </div>
    </div>

    @error('airportNotFound')
    <div class="validation-error mt-2">{{ $message }}</div>
    @enderror

    @error('bearingWarning')
    @if(!empty($message))
    <div class="validation-error mt-2">{{ $message }}</div>
    @endif
    @enderror

    <div id="filters" class="hide-filters">             
        <div class="row g-3 mt-3 pb-4 justify-content-center bt">
            
            <div class="col-sm-12 text-start">
                <label>Weather parameters</label>
                
                @foreach(\App\Http\Controllers\ScoreController::$score_types as $k => $s)
                @if(str_starts_with($k, 'METAR'))
                <div class="mt-1">
                    <div class="btn-group" role="group" aria-label="Basic radio toggle button group">
                        <input type="radio" class="btn-check red" name="scores[{{ $k }}]" value="-1" id="{{ $k }}_exclude" {{ (!empty(old('scores')) && old('scores')[$k] == -1) ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="{{ $k }}_exclude">
                            <i class="fa-solid fa-xmark"></i>
                            <span class="visually-hidden">Exclude</span>
                        </label>
                        
                        <input type="radio" class="btn-check light" name="scores[{{ $k }}]" value="0" id="{{ $k }}_neutral" {{ (empty(old('scores')) || (!empty(old('scores')) && old('scores')[$k] == 0)) ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="{{ $k }}_neutral">
                            <i class="fa-solid fa-slash-forward"></i>
                            <span class="visually-hidden">Neutral</span>
                        </label>
                        
                        <input type="radio" class="btn-check green" name="scores[{{ $k }}]" value="1" id="{{ $k }}_include" {{ (!empty(old('scores')) && old('scores')[$k] == 1) ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="{{ $k }}_include">
                            <i class="fa-solid fa-check"></i>
                            <span class="visually-hidden">Include</span>
                        </label>
                    </div>
                    <i class="ms-2 fa {{ $s['icon'] }}"></i>&nbsp;{{ $s['desc'] }}
                </div>
                @endif
                @endforeach
            </div>
            
            <div class="col-sm-12 text-start">
                
                <label>Meteo Condition</label>
                <div>
                    <div class="btn-group" role="group" aria-label="Basic radio toggle button group">
                        <input type="radio" class="btn-check light" name="metcondition" value="ANY" id="metcondition_all"  {{ (old('metcondition') == null || old('metcondition') == "ANY") ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width-meteo" for="metcondition_all">
                            Any
                        </label>
                        
                        <input type="radio" class="btn-check red" name="metcondition" value="IFR" id="metcondition_ifr" {{ old('metcondition') == "IFR" ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width-meteo" for="metcondition_ifr">
                            IFR
                        </label>
                        
                        <input type="radio" class="btn-check green" name="metcondition" value="VFR" id="metcondition_vfr" {{ old('metcondition') == "VFR" ? 'checked' : null }}>
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
                        <input type="radio" class="btn-check red" name="scores[{{ $k }}]" value="-1" id="{{ $k }}_exclude" {{ (!empty(old('scores')) && old('scores')[$k] == -1) ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="{{ $k }}_exclude">
                            <i class="fa-solid fa-xmark"></i>
                            <span class="visually-hidden">Exclude</span>
                        </label>
                        
                        <input type="radio" class="btn-check light" name="scores[{{ $k }}]" value="0" id="{{ $k }}_neutral" {{ (empty(old('scores')) || (!empty(old('scores')) && old('scores')[$k] == 0)) ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="{{ $k }}_neutral">
                            <i class="fa-solid fa-slash-forward"></i>
                            <span class="visually-hidden">Neutral</span>
                        </label>
                        
                        <input type="radio" class="btn-check green" name="scores[{{ $k }}]" value="1" id="{{ $k }}_include" {{ (!empty(old('scores')) && old('scores')[$k] == 1) ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="{{ $k }}_include">
                            <i class="fa-solid fa-check"></i>
                            <span class="visually-hidden">Include</span>
                        </label>
                    </div>
                    <i class="ms-2 fa {{ $s['icon'] }}"></i>&nbsp;{{ $s['desc'] }}
                </div>
                @endif
                @endforeach
                
                <label class="pt-4">Destination parameters</label>
                
                <div class="mt-1">
                    <div class="btn-group" role="group" aria-label="Basic radio toggle button group">
                        <input type="radio" class="btn-check red" name="destinationWithRoutesOnly" value="-1" id="destinationWithRoutesOnly_exclude" {{ old('destinationWithRoutesOnly') == -1 ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="destinationWithRoutesOnly_exclude">
                            <i class="fa-solid fa-xmark"></i>
                            <span class="visually-hidden">Exclude</span>
                        </label>
                        
                        <input type="radio" class="btn-check light" name="destinationWithRoutesOnly" value="0" id="destinationWithRoutesOnly_neutral" {{ (old('destinationWithRoutesOnly') == null || old('destinationWithRoutesOnly') == 0) ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="destinationWithRoutesOnly_neutral">
                            <i class="fa-solid fa-slash-forward"></i>
                            <span class="visually-hidden">Neutral</span>
                        </label>
                        
                        <input type="radio" class="btn-check green" name="destinationWithRoutesOnly" value="1" id="destinationWithRoutesOnly_include" {{ old('destinationWithRoutesOnly') == 1 ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="destinationWithRoutesOnly_include">
                            <i class="fa-solid fa-check"></i>
                            <span class="visually-hidden">Include</span>
                        </label>
                    </div>
                    <i class="ms-2 fa fa-route"></i>&nbsp;With routes only
                </div>
                
                <div class="mt-1">
                    <div class="btn-group" role="group" aria-label="Basic radio toggle button group">
                        <input type="radio" class="btn-check red" name="destinationRunwayLights" value="-1" id="destinationRunwayLights_exclude" {{ old('destinationRunwayLights') == -1 ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="destinationRunwayLights_exclude">
                            <i class="fa-solid fa-xmark"></i>
                            <span class="visually-hidden">Exclude</span>
                        </label>
                        
                        <input type="radio" class="btn-check light" name="destinationRunwayLights" value="0" id="destinationRunwayLights_neutral" {{ (old('destinationRunwayLights') == null || old('destinationRunwayLights') == 0) ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="destinationRunwayLights_neutral">
                            <i class="fa-solid fa-slash-forward"></i>
                            <span class="visually-hidden">Neutral</span>
                        </label>
                        
                        <input type="radio" class="btn-check green" name="destinationRunwayLights" value="1" id="destinationRunwayLights_include" {{ old('destinationRunwayLights') == 1 ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="destinationRunwayLights_include">
                            <i class="fa-solid fa-check"></i>
                            <span class="visually-hidden">Include</span>
                        </label>
                    </div>
                    <i class="ms-2 fa fa-lightbulb-on"></i>&nbsp;Runway with lights
                </div>
                
                <div class="mt-1">
                    <div class="btn-group" role="group" aria-label="Basic radio toggle button group">
                        <input type="radio" class="btn-check light" name="destinationAirbases" value="-1" id="destinationAirbases_exclude" {{ (old('destinationAirbases') == null || old('destinationAirbases') == -1) ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="destinationAirbases_exclude">
                            <i class="fa-solid fa-xmark"></i>
                            <span class="visually-hidden">Exclude</span>
                        </label>
                        
                        <input type="radio" class="btn-check light" name="destinationAirbases" value="0" id="destinationAirbases_neutral" {{ (old('destinationAirbases') === 0) ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="destinationAirbases_neutral">
                            <i class="fa-solid fa-slash-forward"></i>
                            <span class="visually-hidden">Neutral</span>
                        </label>
                        
                        <input type="radio" class="btn-check green" name="destinationAirbases" value="1" id="destinationAirbases_include" {{ old('destinationAirbases') == 1 ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="destinationAirbases_include">
                            <i class="fa-solid fa-check"></i>
                            <span class="visually-hidden">Exclude</span>
                        </label>
                    </div>
                    <i class="ms-2 fa fa-jet-fighter"></i>&nbsp;Airbases
                </div>
                
                <label class="pt-4">Flight direction</label>
                
                <!-- Get validation errors -->
                @error('flightDirection')
                <div class="validation
                        -error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
                
                <div class="mt-1">
                    <div class="btn-group" role="group" aria-label="Basic radio toggle button group">
                        <input type="radio" class="btn-check light" name="flightDirection" value="0" id="flightDirection_neutral" {{ (old('flightDirection') == null || old('flightDirection') == 0) ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="flightDirection_neutral">
                            <i class="fa-solid fa-slash-forward"></i>
                            <span class="visually-hidden">Neutral</span>
                        </label>
                        
                        <input type="radio" class="btn-check green" name="flightDirection" value="N" id="flightDirection_north" {{ old('flightDirection') == 'N' ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="flightDirection_north">
                            N
                        </label>
                        
                        <input type="radio" class="btn-check green" name="flightDirection" value="NE" id="flightDirection_northeast" {{ old('flightDirection') == 'NE' ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="flightDirection_northeast">
                            NE
                        </label>
                        
                        <input type="radio" class="btn-check green" name="flightDirection" value="E" id="flightDirection_east" {{ old('flightDirection') == 'E' ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="flightDirection_east">
                            E
                        </label>
                        
                        <input type="radio" class="btn-check green" name="flightDirection" value="SE" id="flightDirection_southeast" {{ old('flightDirection') == 'SE' ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="flightDirection_southeast">
                            SE
                        </label>
                        
                        <input type="radio" class="btn-check green" name="flightDirection" value="S" id="flightDirection_south" {{ old('flightDirection') == 'S' ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="flightDirection_south">
                            S
                        </label>
                        
                        <input type="radio" class="btn-check green" name="flightDirection" value="SW" id="flightDirection_southwest" {{ old('flightDirection') == 'SW' ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="flightDirection_southwest">
                            SW
                        </label>
                        
                        <input type="radio" class="btn-check green" name="flightDirection" value="W" id="flightDirection_west" {{ old('flightDirection') == 'W' ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="flightDirection_west">
                            W
                        </label>
                        
                        <input type="radio" class="btn-check green" name="flightDirection" value="NW" id="flightDirection_northwest" {{ old('flightDirection') == 'NW' ? 'checked' : null }}>
                        <label class="btn btn-sm btn-dark btn-filter-width" for="flightDirection_northwest">
                            NW
                        </label>
                    </div>
                </div>
                
            </div>
            
            <div class="col-sm-12 text-start">
                <label class="d-block">Airport Size</label>
                <div>
                    <div class="form-check form-check-inline mb-0 me-reduced">
                        <input class="form-check-input" type="checkbox" value="small_airport" id="destinationAirportSizeSmall" name="destinationAirportSize[]" checked>
                        <label class="form-check-label" for="destinationAirportSizeSmall">
                            Small
                        </label>
                    </div>
                    <div class="form-check form-check-inline mb-0 me-reduced">
                        <input class="form-check-input" type="checkbox" value="medium_airport" id="destinationAirportSizeMedium" name="destinationAirportSize[]" checked>
                        <label class="form-check-label" for="destinationAirportSizeMedium">
                            Medium
                        </label>
                    </div>
                    <div class="form-check form-check-inline mb-0 me-reduced">
                        <input class="form-check-input" type="checkbox" value="large_airport" id="destinationAirportSizeLarge" name="destinationAirportSize[]" checked>
                        <label class="form-check-label" for="destinationAirportSizeLarge">
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

                <label for="airlines" class="pt-4">
                    Airlines
                </label>
                <u-tags id="airlines" data-input-name="airlines[]">
                    <input list="airlines-list" placeholder="All airlines">
                    <u-datalist id="airlines-list">
                        @foreach($airlines as $airline)
                            <u-option value="{{ $airline->icao_code }}">{{ $airline->name }} ({{ $airline->icao_code }})</u-option>
                        @endforeach
                    </u-datalist>
                </u-tags>
                
                <label for="aircraft" class="pt-4">
                    Aircraft
                </label>
                <u-tags id="aircraft" data-input-name="aircrafts[]">
                    <input list="aircraft-list" placeholder="All airlines">
                    <u-datalist id="aircraft-list">
                        @foreach($aircrafts as $aircraft)
                            <u-option value="{{ $aircraft }}">{{ $aircraft }}</u-option>
                        @endforeach
                    </u-datalist>
                </u-tags>
        
            </div>

            <div class="col-sm-12 align-self-start">
                <button type="submit" class="submitBtn btn btn-primary text-uppercase">
                    Search <i class="fas fa-search"></i>
                </button>
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