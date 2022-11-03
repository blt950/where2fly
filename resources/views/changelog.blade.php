@extends('layouts.app')

@section('title', 'Top List')
@section('content')

<div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">

    @include('layouts.menu')
  
    <main class="px-3">
        <h1 class="mb-3 mt-5">Changelog</h1>
        
        <div class="text-start">
            <h2>Beta 5</h2>
            <ul>
                <li>New design</li>
                <li>Further optimized search times</li>
            </ul>

            <h2>Beta 4</h2>
            <ul>
                <li>Added advanced search</li>
                <li>Added hot airport parameter for airports with 10+ movements</li>
                <li>Added reduced sight parameter</li>
                <li>Added more VATSIM data: Which ATC is online and what event is running until when</li>
            </ul>

            <h2>Beta 3</h2>
            <ul>
                <li>Added new search ranking options, no need to search for bad weather only anymore</li>
                <li>Added possibility to search for 6+ hour flights</li>
                <li>Added country flags to results</li>
                <li>Added tooltips over countries and icons</li>
                <li>Fixed results showing #2 and above, rather than #1 (also explains some obvious missing airports on bad days)</li>
                <li>Fixed proper search error if fields are missed</li>
                <li>Fixed breaking action misintepreted as RVR</li>
            </ul>

            <h2>Beta 2</h2>
            <ul>
                <li>Optimized loading times</li>
                <li>Fixed TAFs</li>
                <li>Fixed old METARs</li>
                <li>Added runway lengths in meters</li>
                <li>Mobile tweaks</li>
            </ul>

            <h2>Beta 1</h2>
            <ul>
                <li>Initial release</li>
            </ul>
        </div>

    </main>
  
    @include('layouts.footer')
</div>

@endsection