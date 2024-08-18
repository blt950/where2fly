@extends('layouts.app')

@section('title', 'Reset Account')
@section('content')

    @include('layouts.title', ['title' => 'Reset Account', 'subtitle' => 'Step 1 of 2'])

    <div class="container">
        <div>

            <form method="POST" action="{{ route('user.reset') }}">
                @csrf
                <div class="mb-3">
                    <label for="email">Email address</label>
                    <input name="email" type="email" class="form-control" id="email" value="{{ old('email') }}">
                    @error('email')
                        <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror

                    <button class="btn btn-primary mt-2">RESET ACCOUNT</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('js')
    @vite('resources/js/map.js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
        })
    </script>
@endsection