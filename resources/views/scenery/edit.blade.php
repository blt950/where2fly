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

        @isset($existingSceneries)
            <div class="alert alert-warning mb-3">
                <b><i class="fas fa-info-circle"></i> These sceneries are already in the database</b>
                @foreach($existingSceneries as $existingScenery)
                    <div>
                        <span class="badge bg-dark">{{ $existingScenery->simulator->shortened_name }}</span>
                        @if($existingScenery->payware == 1)
                            <span class="badge bg-info">Payware</span>
                        @elseif($existingScenery->payware == 0)
                            <span class="badge bg-success">Freeware</span>
                        @else
                            <span class="badge bg-danger">Included</span>
                        @endif
                        {{ $existingScenery->developer->developer }}
                        @if($existingScenery->id == $scenery->id)
                            <b>(This review)</b>
                        @elseif($existingScenery->published == 0)
                            (Awaiting review)
                        @endif
                    </div>
                @endforeach
            </div>
        @endisset

        <form action="{{ route('scenery.update', [$scenery]) }}" method="post">
            @csrf
            <h2 class="mb-0">Developer</h2>
            <small class="form-text text-white-50">Changing this will change the whole developer and connected sceneries ({{ $scenery->developer->sceneries->count() }}x)</small>

            <input type="hidden" name="suggested_by_user_id" value="{{ $scenery->suggested_by_user_id }}">

            <div class="mt-3 mb-3">
                <label for="icao" class="form-label">ICAO</label>
                <input type="text" class="form-control" id="icao" name="icao" maxlength="4" value="{{ $scenery->developer->icao }}" required>
                @error('icao')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="developer" class="form-label">Developer</label>
                <input type="text" class="form-control" id="developer" name="developer" maxlength="256" value="{{ $scenery->developer->developer }}" required>
                @error('developer')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <h2>Scenery: {{ $scenery->simulator->shortened_name }}</h2>
        
            <div class="mb-3">
                <label for="link" class="form-label">Link</label>
                <input type="url" class="form-control" id="link" name="link" maxlength="256" value="{{ $scenery->link }}" required>
                @error('link')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="payware">Payware</label>
                <select class="form-select" id="payware" name="payware" required>
                    <option disabled selected>Select</option>
                    <option value="1" @if($scenery->payware == 1) selected @endif>Payware</option>
                    <option value="0" @if($scenery->payware == 0) selected @endif>Freeware</option>
                </select>
                @error('payware')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <input class="form-check-input" type="checkbox" id="published" value="1" name="published" {{ ($scenery->published) ? 'checked' : null }}>
                <label class="form-check-label" for="published">
                    <b>Published</b>
                </label>
                @error('published')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('scenery.delete', [$scenery]) }}" class="btn btn-danger mt-3" onclick="return confirm('Are you sure you want to delete this scenery?')">
                    <i class="fas fa-trash"></i>
                    Delete
                </a>
                <button type="submit" class="btn btn-primary mt-3">Save</button>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var route = "{{ route('scenery.edit', ['x']) }}";
            route = route.replace(/^https?:\/\/[^\/]+/, '');
            umami.track(props => ({ ...props, url: route }));
        });
    </script>
@endsection