@extends('layouts.app')

@section('title', 'Account')
@section('content')

    @include('layouts.title', ['title' => 'Account Settings'])

    <div class="container">
        <h2>Reset password</h2>
        <p class="font-work-sans">You may reset you password by performing an account reset. Your data will <b>not</b> be lost.</p>
        <a href="{{ route('account.recovery') }}" class="btn btn-primary">Reset password</a>

        <h2 class="mt-4">Delete account</h2>
        <p class="font-work-sans">Deleting your account will remove all your data from the system. This action is irreversible.</p>
        <form method="POST" action="{{ route('user.delete') }}">
            @csrf
            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete your account?')">Delete account</button>
        </form>
    </div>
@endsection