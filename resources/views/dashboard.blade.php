@extends('layouts.app')

@section('title', 'Search')
@section('content')

<div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">

    @include('layouts.menu')
  
    <main class="px-3">
      <h1 class="mb-3">What kind of flight do you want?</h1>
      <form class="row g-3 justify-content-center">
        <div class="col-sm-2 text-start">
          <label for="departure">Departure</label>
          <input type="text" class="form-control" id="departure" placeholder="ICAO">
        </div>
        <div class="col-sm-3 text-start">
            <label for="codeletter">Destination Handling Code</label>
            <select class="form-control" id="codeletter">
                <option disabled selected>Choose</option>
                <option>A (PIPER/CESSNA)</option>
                <option>B (CRJ/DHC)</option>
                <option>C (737-700/A320/ERJ)</option>
                <option>D (B767/A310)</option>
                <option>E (B777/B787/A330)</option>
                <option>F (747-8/A380)</option>
            </select>
        </div>
        <div class="col-sm-2 text-start">
            <label for="airtime">Intended Air Time</label>
            <select class="form-control" id="airtime">
                <option disabled selected>Choose</option>
                <option>1 hour or less</option>
                <option>1-2 hours</option>
                <option>2-3 hours</option>
                <option>3-4 hours</option>
                <option>4-5 hours</option>
            </select>
        </div>
        <div class="col-sm-2 align-self-end">
            <button role="submit" href="#" class="btn btn-secondary text-white">Find destination</button>
        </div>
    </form>
    </main>
  
    @include('layouts.footer')
</div>

@endsection