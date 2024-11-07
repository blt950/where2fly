@extends('layouts.app')

@section('meta-description')
    <meta name="description" content="Always struggling to decide where to fly? Find some suggested destinations with fun weather and coverage!">
@endsection

@section('resources')
    @vite('resources/js/nouislider.js')
    @vite('resources/js/multiselect.js')
@endsection

@section('content')
    @include('layouts.title', ['title' => 'Search for your flight', 'subtitle' => 'Find destinations based on your weather or coverage criteria'])

    <div class="container">
        @include('front.parts.tabs')
        @include('front.parts.form', ['icao' => 'departure', 'area' => 'destination'])
    </div>
@endsection

@section('js')
    @vite('resources/js/functions/tooltip.js')
    @vite('resources/js/functions/searchForm.js')
    @vite('resources/js/functions/tags.js')
    @include('front.parts.sliders')
    <script>
        // Focus input when clicking on u-tags whitespace
        document.querySelectorAll('u-datalist').forEach(datalist => {
            const parent = datalist.parentElement;
            if (parent.tagName === 'U-TAGS') {
                parent.addEventListener('click', event => {
                    if (event.target === parent) {
                        parent.querySelector('input').focus();
                    }
                });
            }
        });

        // Prevent adding tags that don't exist in u-option's and add hidden input to form
        document.querySelectorAll('u-tags').forEach(element => {
            element.addEventListener('tags', (event) => {
                const element = event.target;
                const value = event.detail.item.value;
                const options = Array.from(document.querySelectorAll('u-option')).map(option => option.value);

                if (!options.includes(value)) {
                    event.preventDefault();
                } else {
                    if (event.detail.action === 'add') {

                        // Create a hidden input element with the data-input-name and value equal to 'value'
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = element.dataset.inputName;
                        input.value = value;
                        input.dataset.tagValue = value; // For easy identification later

                        // Append the hidden input the element
                        element.appendChild(input);
                    } else if (event.detail.action === 'remove') {
                        // Find the hidden input element with the corresponding value and remove it
                        const inputToRemove = element.querySelector(`input[type="hidden"][name="${element.dataset.inputName}"][value="${value}"]`);
                        if (inputToRemove) {
                            inputToRemove.remove();
                        }
                    }
                }
            });
        })
    </script>
@endsection