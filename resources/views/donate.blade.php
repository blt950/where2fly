@extends('layouts.app')

@section('title', 'Donate')
@section('content')

    @include('layouts.title', ['title' => 'Donate'])

    <div class="container">
        <div class="text-start">
            
            <p class="font-work-sans">Support Where2Fly by helping covering following costs:</p>
            <ul>
                <li>Server infrastructure</li>
                <li>Domain renewal</li>
                <li>Email hosting</li>
                <li>Occasional coffee to keep myself going</li>
            </ul>

            <div class="text-center mt-5">
                <a href="https://www.paypal.com/donate/?hosted_button_id=Q78V8JW6FTH5A" target="_blank">
                    <img src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" alt="Donate with PayPal" />
                </a>

                <p class="font-work-sans mt-4"><i>Donations are not refundable.</i></p>
            </div>
        </div>
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