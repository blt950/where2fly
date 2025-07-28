@extends('layouts.app')

@section('title', 'Reset Account')
@section('content')

    @include('layouts.title', ['title' => 'Reset Account', 'subtitle' => 'Step 1 of 2'])

    <div class="container">
        <div>

            <form method="POST" action="{{ route('account.recovery') }}">
                @csrf
                <div class="mb-3">
                    <label for="email">Email address</label>
                    <input name="email" type="email" class="form-control" id="email" value="{{ old('email') }}">
                    @error('email')
                        <div class="validation-error"><i class="fa-sharp fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror

                    <button class="btn btn-primary mt-2">RESET ACCOUNT</button>
                </div>
            </form>
        </div>
    </div>
@endsection