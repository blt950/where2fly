@extends('layouts.app')

@section('meta-description')
<meta name="description" content="Login into your account">
@endsection

@section('resources')
    <x-turnstile.scripts />
@endsection

@section('title', 'Login')
@section('content')

    @include('layouts.title', ['title' => 'Login'])

    <div class="container">
        <div>
            <div class="mb-4">
                <a class="font-size-1rem" href="{{ route('register') }}">
                    <i class="fa-sharp fa-user-plus"></i> Don't have an account? Register here
                </a>
            </div>

            <form method="POST" action="{{ route('user.login') }}">
                @csrf
                <div class="mb-3">
                    <label for="username">Username <small class="form-text text-white-50">or e-mail</small></label>
                    <input name="username" type="text" class="form-control" id="username" value="{{ old('username') }}">
                    @error('username')
                        <div class="validation-error"><i class="fa-sharp fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password">Password</label>
                    <input name="password" type="password" class="form-control" id="password">
                    <a class="" href="{{ route('account.recovery') }}">Forgot your password?</a>
                    @error('password')
                        <div class="validation-error"><i class="fa-sharp fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>

                <x-turnstile
                    data-action="login"
                    data-theme="light"
                    data-language="en"
                />
                @error('cf-turnstile-response')
                    <div class="validation-error"><i class="fa-sharp fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror

                <div class="mt-2 mb-3">
                    <input name="remember" type="checkbox" class="form-check-input" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember">Remember me</label>
                </div>

                

                <button class="btn btn-primary">LOGIN</button>
            </form>
        </div>
    </div>
@endsection