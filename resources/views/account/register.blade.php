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
                    <i class="fa-sharp fa-check-circle text-success"></i>
                    <span>Create personal airports lists visible on the map</span>
                </div>
                <div>
                    <i class="fa-sharp fa-check-circle text-success"></i>
                    <span>Use your own lists as search whitelists</span>
                </div>
                <div>
                    <i class="fa-sharp fa-check-circle text-success"></i>
                    <span>Contribute with scenery links for airports</span>
                </div>
            </div>
        </div>
        <div>
            <h2 class="mb-3">Registration</h2>
            <form method="POST" action="{{ route('user.register') }}">
                @csrf
                <div class="mb-3">
                    <label for="username">Username</label>
                    <small class="form-text text-white-50">Used to login and it's visible to other users.</small>
                    <input name="username" type="text" class="form-control" id="username" value="{{ old('username') }}" required>
                    @error('username')
                        <div class="validation-error"><i class="fa-sharp fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email">Email address</label>
                    <small class="form-text text-white-50">Only used to verify or recover your account, no marketing.</small>
                    <input name="email" type="email" class="form-control" id="email" value="{{ old('email') }}" required>
                    @error('email')
                        <div class="validation-error"><i class="fa-sharp fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="password">Password</label>
                    <input name="password" type="password" class="form-control" id="password" required>
                    @error('password')
                        <div class="validation-error"><i class="fa-sharp fa-exclamation-triangle"></i> {{ $message }}</div>
                    @else
                        
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="confirmPassword">Confirm Password</label>
                    <input name="password_confirmation" type="password" class="form-control" id="confirmPassword" required>
                    @error('confirm_password')
                        <div class="validation-error"><i class="fa-sharp fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>

                <!-- accept terms checkbox -->
                <div class="form-check mb-3">
                    <input name="privacy_policy" type="checkbox" class="form-check-input" id="acceptTerms" required>
                    <label class="form-check-label" for="acceptTerms">I accept the <a href="{{ route('privacy') }}" target="_blank">privacy policy</a></label>
                    @error('privacy_policy')
                        <div class="validation-error"><i class="fa-sharp fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>

                <x-turnstile
                    data-action="register"
                    data-theme="light"
                    data-language="en"
                />
                @error('cf-turnstile-response')
                    <div class="validation-error"><i class="fa-sharp fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror

                <button class="btn btn-primary mt-2">REGISTER</button>
            </form>
        </div>
    </div>
@endsection