@extends('layouts.app')

@section('title', 'Search')
@section('content')

<div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">

    @include('layouts.menu')
  
    <main class="px-3">
      <h1 class="mb-3">What kind of flight do you want?</h1>
      <form class="row g-3 justify-content-center" id="form" action="{{ route('search') }}" method="POST">
        @csrf

        <div class="col-sm-2 text-start">
            <label for="departure">Departure</label>
            <input type="text" class="form-control" id="departure" name="departure" placeholder="ICAO" oninput="this.value = this.value.toUpperCase()" maxlength="4" value="{{ old('departure') }}">
            @error('departure')
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

        <div class="col-sm-2 text-start">
            <label for="airtime">Intended Air Time</label>
            <select class="form-control" id="airtime" name="airtime">
                <option disabled selected>Choose</option>
                <option value="1" {{ old('airtime') == "1" ? "selected" : "" }}>1 hour or less</option>
                <option value="2" {{ old('airtime') == "2" ? "selected" : "" }}>1-2 hours</option>
                <option value="3" {{ old('airtime') == "3" ? "selected" : "" }}>2-3 hours</option>
                <option value="4" {{ old('airtime') == "4" ? "selected" : "" }}>3-4 hours</option>
                <option value="5" {{ old('airtime') == "5" ? "selected" : "" }}>4-5 hours</option>
            </select>
            @error('airtime')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-sm-2 align-self-end">
            <button role="button" id="submitBtn" href="#" class="btn btn-secondary text-white">
                Find destination
            </button>
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
    </script>
  
    @include('layouts.footer')
</div>

@endsection