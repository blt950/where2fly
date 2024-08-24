@extends('layouts.app')
@section('title', 'Scenery Contribution')
@section('content')

    @include('layouts.title', ['title' => 'Scenery Contribution'])

    <div class="container">
        <p>Contribute with scenery links for airports. After submission we will review it and add it to the database if it meets our criteria.</p>
        <form action="{{ route('scenery.store') }}" method="post">
            @csrf
            <div class="mb-3">
                <label for="icao" class="form-label">ICAO</label>
                <input type="text" class="form-control" id="icao" name="icao" maxlength="4" value="{{ request()->get('airport') }}" required>
                @error('icao')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="author" class="form-label">Author</label>
                <input type="text" class="form-control" id="author" name="author" maxlength="256" required>
                @error('author')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="link" class="form-label">Link</label>
                <small class="form-text text-white-50">Preferably to the official store or platform</small>
                <input type="url" class="form-control" id="link" name="link" maxlength="256" required>
                @error('link')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="simulator" class="form-label">Simulator</label>
                <select class="form-select" id="simulator" name="simulator" required>
                    @foreach($simulators as $simulator)
                        <option value="{{ $simulator->id }}">{{ $simulator->name }}</option>
                    @endforeach
                </select>
                @error('simulator')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <input class="form-check-input" type="checkbox" id="payware" value="1" name="payware">
                <label class="form-check-label" for="payware">
                    Payware
                </label>
                @error('payware')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>
            
            <button type="submit" class="btn btn-primary mt-3">Submit Contribution</button>
        </form>
    </div>
@endsection

@section('js')
    @vite('resources/js/map.js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            mapInit();
        })
    </script>
@endsection