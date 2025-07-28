@extends('layouts.app')

@section('title', 'Reset Account')
@section('content')

    @include('layouts.title', ['title' => 'Reset Account', 'subtitle' => 'Step 2 of 2'])

    <div class="container">
        <div>

            <form method="POST" action="{{ route('password.update') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-3">
                    <label for="email">Email address</label>
                    <input name="email" type="email" class="form-control" id="email" value="{{ old('email') }}">
                    @error('email')
                        <div class="validation-error"><i class="fa-sharp fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>

                <div class="mt-3">
                    <label for="password">New Password</label>
                    <input name="password" type="password" class="form-control" id="password">
                    @error('password')
                        <div class="validation-error"><i class="fa-sharp fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>

                <div class="mt-3">
                    <label for="confirmPassword">Confirm Password</label>
                    <input name="password_confirmation" type="password" class="form-control" id="confirmPassword">
                    @error('confirm_password')
                        <div class="validation-error"><i class="fa-sharp fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>

                <button class="btn btn-primary mt-3">RESET ACCOUNT</button>
            </form>
        </div>
    </div>
@endsection

@section('js')
    <script>        
        document.addEventListener('DOMContentLoaded', function() {
            var route = "{{ route('password.reset', 'x') }}";
            route = route.replace(/^https?:\/\/[^\/]+/, '');
            umami.track(props => ({ ...props, url: route }));
        });
    </script>
@endsection