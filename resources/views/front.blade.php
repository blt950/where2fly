@extends('layouts.app')

@section('title', 'Search')
@section('content')

<div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">

    @include('layouts.menu')
  
    <main class="px-3">
      <h1 class="mb-3">What kind of flight do you want?</h1>
      <form id="form" action="{{ route('search') }}" method="POST">
        @csrf

        <div class="row g-3 justify-content-center">
            <div class="col-sm-2 text-start">
                <label for="departure">Departure</label>
                <input type="text" class="form-control" id="departure" name="departure" placeholder="ICAO" oninput="this.value = this.value.toUpperCase()" maxlength="4" value="{{ old('departure') }}">
                @error('departure')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-sm-2 text-start">
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
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-sm-2 text-start">
                <label for="slider-airtime">Intended Air Time</label>
                <input type="hidden" id="airtimeMin" name="airtimeMin" value="0">
                <input type="hidden" id="airtimeMax" name="airtimeMax" value="4">
                <div id="slider-airtime" class="mt-1 mb-1"></div>
                <span id="slider-airtime-text">0-4 hours</span>
            </div>

            <div class="col-sm-2 text-start">
                <label for="sorting">Rank by</label>

                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" value="1" id="filterWeather" name="filterWeather" checked>
                    <label class="form-check-label" for="filterWeather">
                        Worst Weather
                    </label>
                    @error('filterWeather')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" value="1" id="filterATC" name="filterATC" checked>
                    <label class="form-check-label" for="filterATC">
                        ATC Coverage
                    </label>
                    @error('filterATC')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1 justify-content-center">
            <div class="col-sm-12 align-self-end">
                <button role="button" type="submit" id="submitBtn" href="#" class="btn btn-lg btn-primary text-white">
                    Find destination
                </button>
            </div>
            <div class="col-sm-12 align-self-end">
                <a href="{{ route('front.advanced') }}">Advanced Search</a>
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