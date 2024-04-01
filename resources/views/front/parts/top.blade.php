<h1 class="mb-0 mt-5">What kind of flight do you want?</h1>
<h2 class="front mb-5">Find destinations based on your weather or coverage criteria</h2>

<div class="btn-group filter" style="text-align: left; padding-bottom: 2rem;">
    <a href="{{ @route('front') }}" class="btn btn-outline-primary {{ Route::is('front') ? 'active' : '' }}"><i class="fas fa-plane-arrival"></i> Find Arrival</a>
    <a href="{{ @route('front.departures') }}" class="btn btn-outline-primary {{ Route::is('front.departures') ? 'active' : '' }}"><i class="fas fa-plane-departure"></i> Find Departure</a>
    <a href="{{ @route('front.routes') }}" class="btn btn-outline-primary {{ Route::is('front.routes') ? 'active' : '' }}"><i class="fas fa-route"></i> Find Routes</a>
</div>