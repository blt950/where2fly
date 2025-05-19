@extends('errors::minimal')

@section('title', __('Server Error'))
@section('code', '500')
@section('message', __('Server Error'))

@section('nudge')
    Help us by reporting this issue so we know what went wrong.
@endsection