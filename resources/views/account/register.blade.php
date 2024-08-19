@extends('layouts.app')

@section('meta-description')
    <meta name="description" content="Register for an Where2Fly account.">
@endsection

@section('resources')
    @turnstileScripts()
@endsection

@section('title', 'Register')
@section('content')

    @include('layouts.title', ['title' => 'Register'])

    <div class="container">
        
        <div class="pb-4 mb-4 border-bottom">
            <h2>Account Perks</h2>
            <div>
                <div>
                    <i class="fas fa-check-circle text-success"></i>
                    <span>Create personal lists of preferred airports</span>
                </div>
                <div>
                    <i class="fas fa-check-circle text-success"></i>
                    <span>Preferences to view only the simulators relevant for you</span>
                </div>
                <div>
                    <i class="fas fa-circle-dashed text-success"></i>
                    <span>Save searches (Coming soon)</span>
                </div>
            </div>
        </div>
        <div>
            <h2 class="mb-3">Registration</h2>
            <form method="POST" action="{{ route('user.register') }}">
                @csrf
                <div class="mb-3">
                    <label for="username">Username</label>
                    <input name="username" type="text" class="form-control" id="username" value="{{ old('username') }}">
                    @error('username')
                        <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email">Email address</label>
                    <input name="email" type="email" class="form-control" id="email" value="{{ old('email') }}">
                    @error('email')
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
                <div class="mb-3">
                    <label for="confirmPassword">Confirm Password</label>
                    <input name="password_confirmation" type="password" class="form-control" id="confirmPassword">
                    @error('confirm_password')
                        <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>

                <x-turnstile
                    data-action="register"
                    data-theme="light"
                    data-language="en"
                />
                @error('cf-turnstile-response')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror

                <button class="btn btn-primary mt-2">REGISTER</button>
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