@extends('layouts.app')

@section('meta-description')
<meta name="description" content="Login into your account">
@endsection

@section('title', 'Login')
@section('content')

    @include('layouts.title', ['title' => 'Login'])

    <div class="container">
        <div>
            <div class="mb-4">
                <a class="font-size-1rem" href="{{ route('register') }}">
                    <i class="fas fa-user-plus"></i> Don't have an account? Register here
                </a>
            </div>

            <form method="POST" action="{{ route('user.login') }}">
                @csrf
                <div class="mb-3">
                    <label for="username">Username</label>
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

                <div class="mb-4">
                    <a href="{{ route('account.recovery') }}">Forgot your password?</a>
                </div>

                <button class="btn btn-primary">LOGIN</button>
            </form>
        </div>
    </div>

    @isset($airportsMapCollection)
        @include('parts.popupContainer', ['airportsMapCollection' => $airportsMapCollection])
    @endisset
@endsection

@section('js')
    @vite('resources/js/cards.js')
    @vite('resources/js/map.js')
    @include('scripts.defaultMap')
@endsection