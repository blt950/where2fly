@extends('layouts.app')
@section('title', 'Scenery Contribution')
@section('content')

    @include('layouts.title', ['title' => 'Scenery Contribution'])

    <div class="container">
        @isset($suggestedByUser)
            <p>Submitted by {{ $suggestedByUser->username }}</p>
        @else
            <p>Submitted by N/A</p>
        @endisset

        <form action="{{ route('scenery.update', $scenery->id) }}" method="post">
            @csrf
            <div class="mb-3">
                <label for="icao" class="form-label">ICAO</label>
                <input type="text" class="form-control" id="icao" name="icao" maxlength="4" value="{{ $scenery->icao }}" required>
                @error('icao')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="author" class="form-label">Author</label>
                <input type="text" class="form-control" id="author" name="author" maxlength="256" value="{{ $scenery->author }}" required>
                @error('author')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="link" class="form-label">Link</label>
                <small class="form-text text-white-50">Preferably to the official store or platform</small>
                <input type="url" class="form-control" id="link" name="link" maxlength="256" value="{{ $scenery->link }}" required>
                @error('link')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="simulator" class="form-label">Simulator</label>
                <select class="form-select" id="simulator" name="simulator" required>
                    @foreach($simulators as $simulator)
                        <option value="{{ $simulator->id }}" @if($simulator->id == $scenery->simulator_id) selected @endif>{{ $simulator->name }}</option>
                    @endforeach
                </select>
                @error('simulator')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <input class="form-check-input" type="checkbox" id="payware" value="1" name="payware" {{ ($scenery->payware) ? 'checked' : null }}>
                <label class="form-check-label" for="payware">
                    Payware
                </label>
                @error('payware')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <input class="form-check-input" type="checkbox" id="published" value="1" name="published" {{ ($scenery->published) ? 'checked' : null }}>
                <label class="form-check-label" for="published">
                    Published
                </label>
                @error('published')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('scenery.delete', $scenery) }}" class="btn btn-danger mt-3" onclick="return confirm('Are you sure you want to delete this scenery?')">
                    <i class="fas fa-trash"></i>
                    Delete
                </a>
                <button type="submit" class="btn btn-primary mt-3">Save</button>
            </div>
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