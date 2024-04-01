@extends('layouts.app')

@section('meta-description')
<meta name="description" content="Details of your search">
@endsection

@section('title', 'Results')
@section('content')

@section('resources')
@vite('resources/js/sortable.js')
@endsection

<div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">

    @include('layouts.menu')

    <main>
        <h1 class="mb-3 mt-5">Search Results</h1>

        <div class="departure-container d-flex align-items-center">
            <dl>
                <dt>Departure<dt>
                <dd>
                    <img class="flag" src="/img/flags/{{ strtolower($departure->iso_country) }}.svg" height="16" data-bs-toggle="tooltip" data-bs-title="{{ getCountryName($departure->iso_country) }}" alt="Flag of {{ getCountryName($departure->iso_country) }}"></img>
                    {{ $departure->icao }}
                </dd>
                <dd>{{ $departure->name }}</dd>
            </dl>

            <i class="fas fa-arrow-right fs-2"></i>

            <dl>
                <dt>Arrival<dt>
                <dd>
                    <img class="flag" src="/img/flags/{{ strtolower($arrival->iso_country) }}.svg" height="16" data-bs-toggle="tooltip" data-bs-title="{{ getCountryName($arrival->iso_country) }}" alt="Flag of {{ getCountryName($arrival->iso_country) }}"></img>
                    {{ $arrival->icao }}
                </dd>
                <dd>{{ $arrival->name }}</dd>
            </dl>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h2>Routes</h2>
            <p class="mb-0">Data since {{ $routes->sort()->first()->first_seen_at->format('Y-m-d') }}</p>
        </div>
        
        <div class="scroll-fade">
            <div class="table-responsive">
                <table class="table table-hover text-start sortable asc">
                    <thead>
                        <tr>
                            <th scope="col"">Flight</th>
                            <th scope="col">Airline</th>
                            <th scope="col">Aircraft</th>
                            <th scope="col">Last seen</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($routes as $route)
                            <tr>
                                <td data-sort="{{ $route->flight_icao }}">{{ $route->flight_icao }}</td>
                                <td data-sort="{{ $route->airline->iata_code }}">
                                    <img
                                        class="airline-logo" 
                                        src="{{ asset('img/airlines/'.$route->airline->iata_code.'.png') }}"
                                    >
                                    {{ $route->airline->name }}
                                </td>
                                <td>
                                    {{ $route->aircrafts->pluck('icao')->sort()->implode(', ') }}
                                </td>
                                <td>{{ $route->last_seen_at->format('Y-m-d') }}</td>
                                <td>
                                    <a class="btn btn-sm float-end font-work-sans text-muted" href="https://dispatch.simbrief.com/options/custom?orig={{ $departure->icao }}&dest={{ $arrival->icao }}&airline={{ $route->airline->icao_code }}&fltnum={{ $route->flight_number }}" target="_blank">
                                        <span>SimBrief</span> <i class="fas fa-up-right-from-square"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach                        
                    </tbody>
                </table>

            </div>

            <div class="alert alert-discord">
                <p class="text-white mb-0">
                    <i class="fa-brands fa-discord"></i> Contribute with suggestions, bug reports and vote on new features in <a class="text-white" href="https://discord.gg/UkFg9Yy4gP" target="_blank">our Discord <i class="fas fa-up-right-from-square"></i></a>
                </p>
            </div>
        </div>

        @include('layouts.legend')

    </main>

    @include('scripts.tooltip')
  
    @include('layouts.footer')
</div>

@endsection