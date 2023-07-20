@extends('layouts.app')

@section('title', 'Changelog')
@section('content')

<div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">

    @include('layouts.menu')
  
    <main>
        <h1 class="mb-3 mt-5">Changelog</h1>
        
        <div class="text-start">

            <h2>v1.0.2</h2>
            <ul>
                <li>Revised the Privacy Policy and removed tracking cookies</li>
            </ul>

            <h2>v1.0.1</h2>
            <ul>
                <li>Changed and increased the default runway length for advanced search, this will give a lot more results</li>
                <li>Fixed duplicate RVR score coming up per runway rather than active</li>
                <li>Fixed incorrect crosswind scoring for a lot of airports</li>
                <li>Fixed missing Kosovo tooltip</li> 
            </ul>

            <h2>v1.0.0</h2>
            <ul>
                <li>Hurray! Finally out of beta!</li>
                <li>Added SimBrief dispatch shortcut on search results</li>
                <li>Added cookie consent and privacy page</li>
                <li>Fixed and perfected the responsiveness for smaller devices</li>
                <li>Fixed the page jumping when tooltips for icons open</li>
                <li>Fixed search results showing closed or heli aerodromes</li>
                <li>Fixed an issue with going back would leave search button disabled in some browsers</li>
            </ul>

            <h2>Beta 5</h2>
            <ul>
                <li>New design</li>
                <li>Added new and better paramter icons</li>
                <li>Added thousands delimiter on values</li>
                <li>Fixed mobile and tablet optimizations</li>
                <li>Fixed further optimized search times</li>
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