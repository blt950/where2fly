<?php

namespace App\Http\Controllers;

use App\Helpers\MapHelper;
use App\Models\Airport;
use App\Models\Scenery;
use App\Models\SceneryDeveloper;
use App\Models\Simulator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SceneryController extends Controller
{
    /**
     * The contribution form. `?icao=` lists what the airport already has; not `?airport=`, which
     * in the shell selects an airport on the map.
     */
    public function create(Request $request)
    {
        if ($request->filled('airport')) {
            return redirect()->route('scenery.create', ['icao' => $request->query('airport')]);
        }

        $icao = strtoupper((string) $request->query('icao'));
        $airport = Airport::where('icao', $icao)->with('sceneryDevelopers.sceneries.simulator', 'sceneryDevelopers.sceneries.developer')->first();

        return Inertia::render('SceneryCreate', [
            'icao' => $icao,
            'simulators' => Simulator::all()->sortBy('order')->values()->map(fn (Simulator $simulator) => ['id' => $simulator->id, 'name' => $simulator->name]),
            'existing' => $airport ? $this->existingSceneries($airport->sceneryDevelopers->flatMap->sceneries) : null,
        ])->withViewData(['title' => 'Scenery Contribution']);
    }

    /**
     * The sceneries an airport or developer already has, as the forms list them
     */
    private function existingSceneries(iterable $sceneries, ?Scenery $current = null): array
    {
        return collect($sceneries)->map(fn (Scenery $scenery) => [
            'id' => $scenery->id,
            'simulator' => $scenery->simulator->shortened_name,
            'payware' => (int) $scenery->payware,
            'developer' => $scenery->developer->developer,
            'published' => (bool) $scenery->published,
            'current' => $scenery->is($current),
        ])->values()->all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'icao' => 'required|max:10|exists:airports,icao',
            'developer' => 'required|string',
            'link' => 'required|url',
            'payware' => 'required|boolean',
            'simulators' => 'required|array',
        ]);

        // Use existing scenery or create a new one
        $scenery = SceneryDeveloper::where('developer', $request->developer)->where('icao', strtoupper($request->icao))->first();
        if (! $scenery) {
            $scenery = new SceneryDeveloper();
            $scenery->icao = strtoupper($request->icao);
            $scenery->developer = $request->developer;
            $scenery->airport_id = Airport::where('icao', $request->icao)->first()->id;
            $scenery->save();
        }

        // Attach the simulator to the scenery
        if ($request->simulators) {
            foreach ($request->simulators as $simulatorId) {
                Scenery::create([
                    'scenery_developer_id' => $scenery->id,
                    'simulator_id' => $simulatorId,
                    'link' => $request->link,
                    'payware' => (bool) $request->payware,
                    'published' => false,
                    'source' => 'user_contribution',
                    'suggested_by_user_id' => Auth::id(),
                ]);
            }
        }

        return redirect()->route('scenery.create')->with('success', 'Thank you for your contribution. We will review it and add it to the database if it meets our criteria.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Scenery $scenery)
    {
        $this->authorize('update', $scenery);

        $developer = $scenery->developer->load('sceneries.simulator', 'sceneries.developer');

        return Inertia::render('SceneryEdit', [
            'scenery' => [
                'id' => $scenery->id,
                'simulator' => $scenery->simulator->shortened_name,
                'link' => $scenery->link,
                'payware' => (int) $scenery->payware,
                'published' => (bool) $scenery->published,
                'suggestedByUserId' => $scenery->suggested_by_user_id,
                'icao' => $developer->icao,
                'developer' => $developer->developer,
            ],
            'existing' => $this->existingSceneries($developer->sceneries, $scenery),
            'suggestedBy' => $scenery->suggestedByUser?->username,
        ])->withViewData(['title' => 'Scenery Contribution']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Scenery $scenery)
    {
        $this->authorize('update', $scenery);

        $request->validate([
            'icao' => 'required|max:10|exists:airports,icao',
            'developer' => 'required|string',
            'link' => 'required|url',
            'payware' => 'required|boolean',
            'published' => 'boolean',
            'suggested_by_user_id' => 'nullable|integer',
        ]);

        $sceneryDeveloper = $scenery->developer;

        $sceneryDeveloper->icao = strtoupper($request->icao);
        $sceneryDeveloper->developer = $request->developer;
        $sceneryDeveloper->save();

        $scenery->link = $request->link;
        $scenery->payware = (bool) $request->payware;
        $scenery->published = (bool) $request->published;
        $scenery->source = 'user_contribution';
        $scenery->suggested_by_user_id = ($request->suggested_by_user_id) ? $request->suggested_by_user_id : Auth::id();
        $scenery->save();

        return redirect()->route('admin')->with('success', 'Scenery updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Scenery $scenery)
    {
        $this->authorize('delete', $scenery);
        Scenery::destroy($scenery->id);

        return redirect()->route('admin')->with('success', 'Scenery deleted successfully.');
    }

    /**
     * Index of all airports with scenery
     */
    public function indexAirports(Request $request, ?string $filteredSim = null): Response
    {
        $availableSimulators = Simulator::whereHas('sceneries')->get()->sortBy('order');
        $filteredSimulator = $availableSimulators->where('shortened_name', $filteredSim)->first();

        return Inertia::render('Scenery', [
            // Thousands of airports (~350 KB): fetched after the page is up, not inlined in its HTML
            'airportMapData' => Inertia::defer(fn () => (object) MapHelper::generateAirportMapDataFromAirports(
                Airport::publishedSceneries(true, $filteredSimulator?->id)->get()
            )),
            'simulators' => $availableSimulators->pluck('shortened_name')->values(),
            'filteredSimulator' => $filteredSimulator?->shortened_name,
        ])->withViewData([
            'title' => 'Scenery',
            'metaDescription' => 'Find scenery for your flight simulator',
        ]);
    }
}
