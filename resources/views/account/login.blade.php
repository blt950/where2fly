@extends('layouts.app')

@section('meta-description')
<meta name="description" content="Login into your account">
@endsection

@section('title', 'Register')
@section('content')

    @include('layouts.title', ['title' => 'Login'])

    <div class="container">
        <div>
            <div class="mb-4">
                <a href="{{ route('register') }}">Don't have an account? Register here</a>
            </div>

            <form method="POST" action="{{ route('user.register') }}">
                @csrf
                <div class="mb-3">
                    <label for="username">Username or e-mail</label>
                    <input name="username" type="text" class="form-control" id="username" value="{{ old('username') }}">
                    @error('username')
                        <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password">Password</label>
                    <input name="password" type="password" class="form-control" id="password">
                    @error('password')
                        <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>

                <button class="btn btn-primary">LOGIN</button>
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