@extends('layouts.app')

@section('meta-description')
<meta name="description" content="Check out the details of the new updates">
@endsection

@section('title', 'Changelog')
@section('content')

    @include('layouts.title', ['title' => 'Changelog'])

    <div class="container">
        <div class="text-start">

            <h2 class="mb-0">v2.5.0</h2>
            <span class="badge ps-0 pb-2">2025-02-01</span>
            <ul>
                <li><a href="https://github.com/blt950/where2fly" target="_blank">The project is now open source!</a></li>
                <li>Added MSFS2024 scenery support</li>
                <li>Added simulator name to lists</li>
                <li>Fixed SimMarket links</li>
            </ul>

            <h2 class="mb-0">v2.4.0</h2>
            <span class="badge ps-0 pb-2">2024-12-30</span>
            <ul>
                <li>Added temperature filter</li>
                <li>Fixed slider name to correspond with direction</li>
            </ul>

            <h2 class="mb-0">v2.3.3</h2>
            <span class="badge ps-0 pb-2">2024-12-08</span>
            <ul>
                <li>Fixed a console error that could stop searches working</li>
            </ul>

            <h2 class="mb-0">v2.3.2</h2>
            <span class="badge ps-0 pb-2">2024-12-08</span>
            <ul>
                <li>Fixed a better contrast for scenery results</li>
                <li>Changed MSFS naming to MSFS2020 and added MSFS2024 for future a update</li>
            </ul>

            <h2 class="mb-0">v2.3.1</h2>
            <span class="badge ps-0 pb-2">2024-11-30</span>
            <ul>
                <li>Fixed map going black in some flight search results</li>
            </ul>

            <h2 class="mb-0">v2.3.0</h2>
            <span class="badge ps-0 pb-2">2024-11-17</span>
            <ul>
                <li>Added filters for continent, country and US states</li>
                <li>Added possibility to bookmark search results in the browser</li>
                <li>Fixed filters no longer applied to the primary airport if ICAO code is not provided</li>
                <li>Improved search speed on airline and aircraft search</li>
                <li>Improved the multi select input field</li>
            </ul>

            <h2 class="mb-0">v2.2.0</h2>
            <span class="badge ps-0 pb-2">2024-11-03</span>
            <ul>
                <li>Added day/night map overlay</li>
                <li>Added option to hide/show lists on the map</li>
                <li>Fixed highlighting of aircraft in search results</li>
                <li>Fixed an issue with the map not loading</li>
                <li>Optimized loading times</li>
            </ul>

            <h2 class="mb-0">v2.1.2</h2>
            <span class="badge ps-0 pb-2">2024-10-13</span>
            <ul>
                <li>Fixed throttle and sending issues with email confirmations</li>
                <li>Fixed content for user using font scaling</li>
                <li>Updated airport database to October 2024</li>
            </ul>

            <h2 class="mb-0">v2.1.1</h2>
            <span class="badge ps-0 pb-2">2024-10-13</span>
            <ul>
                <li>Fixed scenery suggestion opening wrong page</li>
            </ul>

            <h2 class="mb-0">v2.1.0</h2>
            <span class="badge ps-0 pb-2">2024-10-13</span>
            <ul>
                <li>Added integration with FS Addon Compare for scenery prices for MSFS</li>
                <li>Added a dedicated page to look for scenery</li>
                <li>Added a tags for airports you have in your lists</li>
                <li>Added question mark to explain the aircraft code letter in search</li>
                <li>Added support for multiple simulators for scenery</li>
                <li>Added FR24 link to flight search results</li>
                <li>Added and redesigned the flights card with a sortable table</li>
                <li>Fixed an error where lists would fail to save if they had empty lines in them</li>
                <li>Fixed the awaiting email verification message to show your email to detect mistakes</li>
                <li>Fixed tab sequence on login page</li>
            </ul>

            <h2 class="mb-0">v2.0.8</h2>
            <span class="badge ps-0 pb-2">2024-09-13</span>
            <ul>
                <li>Fixed issue with long-haul searches with random ICAO returning no or limited results</li>
                <li>Fixed login functionality to allow using email as username</li>
                <li>Fixed airports displaying incorrectly on the map for certain long-haul results over the pacific</li>
                <li>Fixed issue where ICAO codes were not consistently visible when zooming out on airport results</li>
            </ul>

            <h2 class="mb-0">v2.0.7</h2>
            <span class="badge ps-0 pb-2">2024-09-11</span>
            <ul>
                <li>Fixed an issue where airports with new ICAO codes would not be found when adding to lists</li>
                <li>Fixed local codes (e.g. N68) not working to add airports to lists, especially relevant for US airfields</li>
            </ul>

            <h2 class="mb-0">v2.0.6</h2>
            <span class="badge ps-0 pb-2">2024-09-06</span>
            <ul>
                <li>Fixed an issue where flights would not always load when using aircraft filter in some search results</li>
            </ul>

            <h2 class="mb-0">v2.0.5</h2>
            <span class="badge ps-0 pb-2">2024-09-05</span>
            <ul>
                <li>Optimized loading of user's airport lists by caching the result</li>
                <li>Fixed table in top list not showing active row if airport is clicked on map</li>
                <li>Fixed flights sometimes not showing when filtering on aircraft type</li>
                <li>Fixed flashing labels when clicking on the map</li>
                <li>Fixed 'remember me' on login not working</li>
            </ul>

            <h2 class="mb-0">v2.0.4</h2>
            <span class="badge ps-0 pb-2">2024-09-03</span>
            <ul>
                <li>Improved map performance by loading data dynamically, only when needed, instead of all at once</li>
                <li>Removed restrictions on the number of airports that can be displayed in large lists</li>
                <li>Fixed an issue where hovering over a cluster now displays its coverage area correctly</li>
                <li>Fixed an issue where the destination label would not consistently turn yellow when selected in search results</li>
                <li>Fixed a bug where the airport card would not appear when an airport is opened in flight results</li>
                <li>Fixed table sorting not working if search results has no interesting weather</li>
                <li>Ensured that airports selected from the list are now highlighted on the map as well.</li>
            </ul>

            <h2 class="mb-0">v2.0.3</h2>
            <span class="badge ps-0 pb-2">2024-08-29</span>
            <ul>
                <li>Fixed errors related to creation and editing of lists</li>
                <li>Fixed missing border on flags causing white flags to disappear</li>
            </ul>

            <h2 class="mb-0">v2.0.2</h2>
            <span class="badge ps-0 pb-2">2024-08-27</span>
            <ul>
                <li>Fixed timeouts for users with very big lists by limiting loaded airports</li>
                <li>Corrected some aircraft types to be ICAO code compatible</li>
            </ul>

            <h2 class="mb-0">v2.0.1</h2>
            <span class="badge ps-0 pb-2">2024-08-27</span>
            <ul>
                <li>Fixed missing "Remember me" checkbox when logging in.</li>
                <li>Fixed server error when editing a list and providing duplicates</li>
                <li>Fixed searches without "Worst Weather" and "ATC coverage" checkboxes serving destinations in a cluster and far away</li>
            </ul>
            
            <h2 class="mb-0">v2.0.0</h2>
            <span class="badge ps-0 pb-2">2024-08-25</span>
            <ul>
                <li>New design with map view</li>
                <li>Added account creation</li>
                <li>Added user defined airport lists</li>
                <li>Added scenery links for airports</li>
                <li>Fixed TAF not working for departure airport</li>
                <li>Improved accessibility</li>
                <li>Optimized search times</li>
            </ul>

            <h2 class="mb-0">v1.8.0</h2>
            <span class="badge ps-0 pb-2">2024-07-27</span>
            <ul>
                <li>Optimized search times with a new database setup</li>
                <li>Added flight direction filter</li>
                <li>Added "Anywhere" destination area</li>
                <li>Added new flights detail view replacing the popover</li>
                <li>Added highlight of filtered aircraft in search result</li>
            </ul>

            <h2 class="mb-0">v1.7.2</h2>
            <span class="badge ps-0 pb-2">2024-07-05</span>
            <ul>
                <li>Fixed incorrect order of icao code in Simbrief links when seaching for departure airports</li>
            </ul>

            <h2 class="mb-0">v1.7.1</h2>
            <span class="badge ps-0 pb-2">2024-04-03</span>
            <ul>
                <li>Fixed incorrect icao code from Simbrief links in search results</li>
            </ul>

            <h2 class="mb-0">v1.7.0</h2>
            <span class="badge ps-0 pb-2">2024-04-01</span>
            <ul>
                <li>Added new search categories: Departure and route</li>
                <li>Added ICAO codes to airline list for easier search</li>
                <li>Added Simbrief shortcuts in route results and top list</li>
                <li>Added arrival or departure search shortcut from top list</li>
                <li>Added possibility to search for local airport codes (e.g. 1L1 instead of K1L1)</li>
                <li>Partly fixed so random deparure + arrival suggestions cross continents for long haul</li>
                <li>Improved German military airfield classification</li>
            </ul>

            <h2 class="mb-0">v1.6.0</h2>
            <span class="badge ps-0 pb-2">2024-03-18</span>
            <ul>
                <li>Added aircraft type filter</li>
                <li>Added airline icons for DHL, Aerologic, Fedex, Cargolux, UPS and Sunclass</li>
            </ul>

            <h2 class="mb-0">v1.5.0</h2>
            <span class="badge ps-0 pb-2">2024-01-14</span>
            <ul>
                <li>Added sorting of results in top list and search results</li>
                <li>Added VATSIM filter to top list</li>
                <li>Fixed slow loading times on front page</li>
            </ul>

            <h2 class="mb-0">v1.4.3</h2>
            <span class="badge ps-0 pb-2">2023-12-28</span>
            <ul>
                <li>Fixed search results displaying airports with too short runways</li>
                <li>Fixed airline popover to only show max 30 flights and sorted them by time</li>
                <li>Changed and made airbases default off in search</li>
                <li>Changed and clarified the code letter search parameter</li>
                <li>Increased flight sample rate to catch more flights</li>
            </ul>
            
            <h2 class="mb-0">v1.4.2</h2>
            <span class="badge ps-0 pb-2">2023-10-19</span>
            <ul>
                <li>Fixed search returning departure suggestion with no arrival suggestions</li>
            </ul>

            <h2 class="mb-0">v1.4.1</h2>
            <span class="badge ps-0 pb-2">2023-09-11</span>
            <ul>
                <li>Fixed some urls resulting in server error</li>
                <li>API: Continent paramter no longer required if whitelist is present</li>
            </ul>

            <h2 class="mb-0">v1.4.0</h2>
            <span class="badge ps-0 pb-2">2023-09-09</span>
            <ul>
                <li>Added a new filtering experience which also has merged normal and advanced search into one</li>
                <li>Added filter for airlines, runway lights, airport sizes</li>
                <li>Added dates to changelog</li>
                <li>Fixed the top list style to match search results</li>
                <li>Fixed input fields zooming the screen on Safari mobile</li>
                <li>Fixed random airport suggestions previously favouring certain airports</li>
                <li>API: No longer in beta!</li>
                <li>API: Tweaked to accommodate new filters</li>
                <li>API: Published <a href="{{ route('api') }}">public documentation</a></li>
            </ul>

            <h2 class="mb-0 mt-4">v1.3.1</h2>
            <span class="badge ps-0 pb-2">2023-08-29</span>
            <ul>
                <li>Fixed airline routes in search results displaying incorrect flights</li>
                <li>Fixed so airports without runways don't show up anymore</li>
                <li>Tweaked some texts</li>
            </ul>

            <h2 class="mb-0 mt-4">v1.3.0</h2>
            <span class="badge ps-0 pb-2">2023-08-27</span>
            <ul>
                <li>Added real world flights to search results (Beta)</li>
                <li>Added option to only search for real world flights in advanced search (Beta)</li>
                <li>Fixed SimBrief link missing origin airport</li>
                <li>Fixed some missing military airport definintions (RAAF and RNAS)</li>
            </ul>

            <h2 class="mb-0 mt-4">v1.2.0</h2>
            <span class="badge ps-0 pb-2">2023-07-29</span>
            <ul>
                <li>Added an API (Beta) to fetch toplist and search</li>
                <li>Added Windy button in results</li>
                <li>Added airbase and airline route exclusion filter in advanced search</li>
                <li>Added notice that departure suggestions are also based on filters</li>
                <li>Fixed error 405 if you enter a search result directly. It'll now redirect to the search page</li>
                <li>Tweaked the design a tiny bit and added some padding to tables</li>
            </ul>

            <h2 class="mb-0 mt-4">v1.1.1</h2>
            <span class="badge ps-0 pb-2">2023-07-24</span>
            <ul>
                <li>Fixed server error if departure is from an airport without METAR available</li>
            </ul>

            <h2 class="mb-0 mt-4">v1.1.0</h2>
            <span class="badge ps-0 pb-2">2023-07-23</span>
            <ul>
                <li>Added random departure suggestions if departure is left blank</li>
                <li>Added a warning if your search ranking criteria are not met</li>
                <li>Added more search suggestions</li>
                <li>Fixed Russia to be split into European and Asian part by the Ural Mountains</li>
                <li>Fixed advanced search only returning large airports</li>
                <li>Fixed IFR/VFR condition filter not filtering correctly</li>
                <li>Fixed METAR timestamp showing visually incorrect minute value</li>
                <li>Fixed search to check if the runway in question is not closed</li>
                <li>Made airtime show in hh:mm format instead of decimals</li>
                <li>Tweaked some texts</li>
            </ul>

            <h2 class="mb-0 mt-4">v1.0.2</h2>
            <span class="badge ps-0 pb-2">2023-07-21</span>
            <ul>
                <li>Revised the Privacy Policy and removed tracking cookies</li>
            </ul>

            <h2 class="mb-0 mt-4">v1.0.1</h2>
            <span class="badge ps-0 pb-2">2023-01-22</span>
            <ul>
                <li>Changed and increased the default runway length for advanced search, this will give a lot more results</li>
                <li>Fixed duplicate RVR score coming up per runway rather than active</li>
                <li>Fixed incorrect crosswind scoring for a lot of airports</li>
                <li>Fixed missing Kosovo tooltip</li> 
            </ul>

            <h2 class="mb-0 mt-4">v1.0.0</h2>
            <span class="badge ps-0 pb-2">2023-01-03</span>
            <ul>
                <li>Hurray! Finally out of beta!</li>
                <li>Added SimBrief dispatch shortcut on search results</li>
                <li>Added cookie consent and privacy page</li>
                <li>Fixed and perfected the responsiveness for smaller devices</li>
                <li>Fixed the page jumping when tooltips for icons open</li>
                <li>Fixed search results showing closed or heli aerodromes</li>
                <li>Fixed an issue with going back would leave search button disabled in some browsers</li>
            </ul>

            <h2 class="mb-0 mt-4">Beta 5</h2>
            <ul>
                <li>New design</li>
                <li>Added new and better paramter icons</li>
                <li>Added thousands delimiter on values</li>
                <li>Fixed mobile and tablet optimizations</li>
                <li>Fixed further optimized search times</li>
            </ul>

            <h2 class="mb-0 mt-4">Beta 4</h2>
            <ul>
                <li>Added advanced search</li>
                <li>Added hot airport parameter for airports with 10+ movements</li>
                <li>Added reduced sight parameter</li>
                <li>Added more VATSIM data: Which ATC is online and what event is running until when</li>
            </ul>

            <h2 class="mb-0 mt-4">Beta 3</h2>
            <ul>
                <li>Added new search ranking options, no need to search for bad weather only anymore</li>
                <li>Added possibility to search for 6+ hour flights</li>
                <li>Added country flags to results</li>
                <li>Added tooltips over countries and icons</li>
                <li>Fixed results showing #2 and above, rather than #1 (also explains some obvious missing airports on bad days)</li>
                <li>Fixed proper search error if fields are missed</li>
                <li>Fixed breaking action misintepreted as RVR</li>
            </ul>

            <h2 class="mb-0 mt-4">Beta 2</h2>
            <ul>
                <li>Optimized loading times</li>
                <li>Fixed TAFs</li>
                <li>Fixed old METARs</li>
                <li>Added runway lengths in meters</li>
                <li>Mobile tweaks</li>
            </ul>

            <h2 class="mb-0 mt-4">Beta 1</h2>
            <ul>
                <li>Initial release</li>
            </ul>
        </div>
    </div>
@endsection