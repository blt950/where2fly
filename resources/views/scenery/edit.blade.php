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
                @foreach($existingSceneries as $scenery)
                    @foreach($scenery->simulators as $simulator)
                        <div>
                            <span class="badge bg-dark">{{ $simulator->shortened_name }}</span>
                            @if($simulator->pivot->payware == 1)
                                <span class="badge bg-info">Payware</span>
                            @elseif($simulator->pivot->payware == 0)
                                <span class="badge bg-success">Freeware</span>
                            @else
                                <span class="badge bg-danger">Included</span>
                            @endif
                            {{ $scenery->developer }}
                            @if($simulator->pivot->published == 0)
                                (Awaiting review)
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>
        @endisset

        <form action="{{ route('scenery.update', [$scenery, $simulator]) }}" method="post">
            @csrf
            <h2>Scenery</h2>

            <div class="mb-3">
                <label for="icao" class="form-label">ICAO</label>
                <input type="text" class="form-control" id="icao" name="icao" maxlength="4" value="{{ $scenery->icao }}" required>
                @error('icao')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="developer" class="form-label">Developer</label>
                <input type="text" class="form-control" id="developer" name="developer" maxlength="256" value="{{ $scenery->developer }}" required>
                @error('developer')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <h2>Simulator</h2>
        
            <div class="mb-3">
                <label for="link" class="form-label">Link</label>
                <input type="url" class="form-control" id="link" name="link" maxlength="256" value="{{ $simulator->pivot->link }}" required>
                @error('link')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="payware">Payware</label>
                <select class="form-select" id="payware" name="payware" required>
                    <option disabled selected>Select</option>
                    <option value="1" @if($simulator->pivot->payware == 1) selected @endif>Payware</option>
                    <option value="0" @if($simulator->pivot->payware == 0) selected @endif>Freeware</option>
                </select>
                @error('payware')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Simulator</label>
                {{ $simulator->shortened_name }}
            </div>

            <div class="mb-3">
                <input class="form-check-input" type="checkbox" id="published" value="1" name="published" {{ ($simulator->pivot->published) ? 'checked' : null }}>
                <label class="form-check-label" for="published">
                    <b>Published</b>
                </label>
                @error('published')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('scenery.delete', [$scenery, $simulator]) }}" class="btn btn-danger mt-3" onclick="return confirm('Are you sure you want to delete this scenery?')">
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
            var route = "{{ route('scenery.edit', ['x', 'x']) }}";
            route = route.replace(/^https?:\/\/[^\/]+/, '');
            umami.track(props => ({ ...props, url: route }));
        });
    </script>
@endsection