@extends('layouts.app')

@section('title', 'Advanced Search')
@section('content')

<div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">

    @include('layouts.menu')
  
    <main class="px-3">
      <h1 class="mb-3">Advanced Search</h1>
      <form id="form" action="{{ route('search.advanced') }}" method="POST">
        @csrf

        <div class="row g-3 justify-content-center">
            <div class="col-sm-2 text-start">
                <label for="departure">Departure</label>
                <input type="text" class="form-control" id="departure" name="departure" placeholder="ICAO" oninput="this.value = this.value.toUpperCase()" maxlength="4" value="{{ old('departure') }}">
                @error('departure')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-sm-3 text-start">
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
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-sm-3 text-start">
                <label for="codeletter">Aircraft Code</label>
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
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>


        </div>
        <div class="row g-3 mt-1 justify-content-center">

            <div class="col-sm-2 text-start">
                <label for="slider-elevation">Arrival Elevation</label>
                <input type="hidden" id="elevationMin" name="elevationMin" value="0">
                <input type="hidden" id="elevationMax" name="elevationMax" value="18000">
                <div id="slider-elevation" class="mt-1 mb-1"></div>
                <span id="slider-elevation-text">0-18000ft</span>
            </div>
            

            <div class="col-sm-3 text-start">
                <label for="slider-rwy">Arrival Runway Length</label>
                <input type="hidden" id="rwyLengthMin" name="rwyLengthMin" value="0">
                <input type="hidden" id="rwyLengthMax" name="rwyLengthMax" value="1000">
                <div id="slider-rwy" class="mt-1 mb-1"></div>
                <span id="slider-rwy-text">0-1000'</span>
            </div>

            <div class="col-sm-3 text-start">
                <label for="slider-airtime">Intended Air Time</label>
                <input type="hidden" id="airtimeMin" name="airtimeMin" value="0">
                <input type="hidden" id="airtimeMax" name="airtimeMax" value="5">
                <div id="slider-airtime" class="mt-1 mb-1"></div>
                <span id="slider-airtime-text">0-5 hours</span>
            </div>

        </div>
        <div class="row g-3 mt-3 justify-content-center">

            <div class="col-sm-3 text-start">
                <label>Weather parameters</label>

                @foreach(\App\Http\Controllers\ScoreController::$score_types as $k => $s)
                    @if(str_starts_with($k, 'METAR'))
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="{{ $k }}" name="scores[]" value="{{ $k }}">
                            <label class="form-check-label" for="{{ $k }}">
                                <i class="fa {{ $s['icon'] }}"></i>&nbsp;{{ $s['desc'] }}
                            </label>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="col-sm-3 text-start">
                <label>Network parameters</label>

                @foreach(\App\Http\Controllers\ScoreController::$score_types as $k => $s)
                    @if(str_starts_with($k, 'VATSIM'))
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="{{ $k }}" name="scores[]" value="{{ $k }}">
                            <label class="form-check-label" for="{{ $k }}">
                                <i class="fa {{ $s['icon'] }}"></i>&nbsp;{{ $s['desc'] }}
                            </label>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="col-sm-2 text-start">
                <label>Meteo Condition</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="metcondition" value="ANY" id="met-any" checked>
                    <label class="form-check-label" for="met-any">
                    Any
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="metcondition" value="IFR" id="met-ifr">
                    <label class="form-check-label" for="met-ifr">
                    IFR
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="metcondition" value="VFR" id="met-vfr">
                    <label class="form-check-label" for="met-vfr">
                    VFR
                    </label>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1 justify-content-center">
            <div class="col-sm-12 align-self-end">
                <button role="button" id="submitBtn" href="#" class="btn btn-lg btn-primary text-white">
                    Find destination
                </button>
            </div>
        </div>

    </form>
    </main>

    <script>
        var button = document.getElementById('submitBtn');
        button.addEventListener('click', function() {
            button.setAttribute('disabled', '')
            button.innerHTML = 'Searching ... <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
            document.getElementById('form').submit()
        });

        // Run scripts when DOM is loaded
        document.addEventListener('DOMContentLoaded', function () {

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
                elevationSliderText.innerHTML = Math.round(values[0]) + '-' + Math.round(values[1]) + 'ft';
                elevationMinInput.value = Math.round(values[0])
                elevationMaxInput.value = Math.round(values[1])
            });

            var rwySlider = document.getElementById('slider-rwy');
            noUiSlider.create(rwySlider, {
                start: [{{ old('rwyLengthMin') ? old('rwyLengthMin') : 0 }}, {{ old('rwyLengthMax') ? old('rwyLengthMax') : 10000 }}],
                step: 500,
                connect: true,
                behaviour: 'drag',
                range: {
                    'min': [0],
                    'max': [16000]
                }
            });

            var rwySliderText = document.getElementById('slider-rwy-text');
            var rwyMinInput = document.getElementById('rwyLengthMin');
            var rwyMaxInput = document.getElementById('rwyLengthMax');
            rwySlider.noUiSlider.on('update', function (values) {
                rwySliderText.innerHTML = Math.round(values[0]) + '-' + Math.round(values[1]) + 'ft / <span class="text-white">' + Math.round(values[0]/3.2808) + '-' + Math.round(values[1]/3.2808) + 'm</span>';
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
                    'max': [24]
                }
            });

            var airtimeSliderText = document.getElementById('slider-airtime-text');
            var airtimeMinInput = document.getElementById('airtimeMin');
            var airtimeMaxInput = document.getElementById('airtimeMax');
            airtimeSlider.noUiSlider.on('update', function (values) {
                airtimeSliderText.innerHTML = Math.round(values[0]) + '-' + Math.round(values[1]) + ' hours';
                airtimeMinInput.value = Math.round(values[0])
                airtimeMaxInput.value = Math.round(values[1])
            });
        }, false);
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
        }, false);
    </script>
  
    @include('layouts.footer')
</div>

@endsection