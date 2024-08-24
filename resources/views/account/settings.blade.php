@extends('layouts.app')

@section('title', 'Account')
@section('content')

    @include('layouts.title', ['title' => 'Account Settings'])

    <div class="container">
        <h2>Delete account</h2>
        <p class="font-work-sans">Deleting your account will remove all your data from the system. This action is irreversible.</p>
        <form method="POST" action="{{ route('user.delete') }}">
            @csrf
            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete your account?')">Delete account</button>
        </form>
    </div>

    @isset($airportsMapCollection)
        @include('parts.popupContainer', ['airportsMapCollection' => ($airportsMapCollection)])
    @endisset
@endsection

@section('js')
    @vite('resources/js/functions/taf.js')
    @vite('resources/js/cards.js')
    @vite('resources/js/map.js')
    @include('scripts.defaultMap')
@endsection