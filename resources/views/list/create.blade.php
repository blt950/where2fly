@extends('layouts.app')
@section('title', 'Your Lists')
@section('content')

    @include('layouts.title', ['title' => 'Your lists'])

    <div class="container">
        <form action="{{ route('list.store') }}" method="post">
            @csrf
            <div class="mb-3">
                <label for="color" class="form-label">Color</label>
                <input type="color" id="color" value="#8596e7" name="color" class="d-block">
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" maxlength="32" required>
                @error('name')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="simulator" class="form-label">Simulator</label>
                <select class="form-select" id="simulator" name="simulator" required>
                    @foreach($simulators as $simulator)
                        <option value="{{ $simulator->id }}">{{ $simulator->name }}</option>
                    @endforeach
                </select>
                @error('simulator')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label for="airports" class="form-label">Airports</label>
                <small class="form-text text-white-50">Separate airports by new line</small>
                <textarea class="form-control h-100" id="airports" name="airports" rows="8" required></textarea>
                @error('airports')
                    <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                @enderror
            </div>

            @if(Auth::user()->admin)
                <div class="mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="public" name="public">
                    <label class="form-check-label" for="public">
                        Public
                    </label>
                    @error('public')
                        <div class="validation-error"><i class="fas fa-exclamation-triangle"></i> {{ $message }}</div>
                    @enderror
                </div>
            @endif
            
            <button type="submit" class="btn btn-primary">Create list</button>
        </form>
    </div>
@endsection