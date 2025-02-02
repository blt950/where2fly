<?php

namespace App\Http\Controllers;

use App\Helpers\MapHelper;
use App\Models\Airport;
use App\Models\Scenery;
use App\Models\SceneryDeveloper;
use App\Models\Simulator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class SceneryController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $availableSimulators = Simulator::all()->sortBy('order');

        $airport = Airport::where('icao', $request->get('airport'))->with('sceneryDevelopers', 'sceneryDevelopers.sceneries')->first();
        $existingDevelopers = null;
        if ($airport) {
            $existingDevelopers = $airport->sceneryDevelopers;
        }

        return view('scenery.create', compact('availableSimulators', 'existingDevelopers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'icao' => 'required|max:4|min:4|exists:airports,icao',
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
            $scenery->airport_id = Airport::where('icao', $request->icao)->get()->first()->id;
            $scenery->save();
        }

        // Attach the simulator to the scenery
        if ($request->simulators) {
            foreach($request->simulators as $simulatorId){
                Scenery::create([
                    'scenery_developer_id' => $scenery->id,
                    'simulator_id' => $simulatorId,
                    'link' => $request->link,
                    'payware' => $request->payware ? true : false,
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

        $availableSimulators = Simulator::all()->sortBy('order');
        $existingSceneries = $scenery->developer->sceneries;
        $suggestedByUser = $scenery->suggestedByUser;

        // Use the manual umami tracking script on this page
        View::share('manualTracking', true);

        return view('scenery.edit', compact('scenery', 'availableSimulators', 'existingSceneries', 'suggestedByUser'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Scenery $scenery)
    {
        $this->authorize('update', $scenery);

        $request->validate([
            'icao' => 'required|max:4|min:4|exists:airports,icao',
            'developer' => 'required|string',
            'link' => 'required|url',
            'payware' => 'required|boolean',
            'published' => 'boolean',
            'suggested_by_user_id' => 'integer',
        ]);

        $sceneryDeveloper = $scenery->developer;

        $sceneryDeveloper->icao = strtoupper($request->icao);
        $sceneryDeveloper->developer = $request->developer;
        $sceneryDeveloper->save();

        $scenery->link = $request->link;
        $scenery->payware = $request->payware ? true : false;
        $scenery->published = $request->published ? true : false;
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
    public function indexAirports(Request $request, ?string $filteredSim = null)
    {
        $availableSimulators = Simulator::whereHas('sceneries')->get();
        $filteredSimulator = $availableSimulators->where('shortened_name', $filteredSim)->first();

        if ($filteredSimulator) {
            $airports = Airport::whereHasPublishedSceneries(true, $filteredSimulator->id)->get();
        } else {
            $airports = Airport::whereHasPublishedSceneries(true)->get();
        }

        $airportMapData = json_encode(MapHelper::generateAirportMapDataFromAirports($airports));
        $airportsCount = $airports->count();

        return view('scenery', compact('airportsCount', 'airportMapData', 'availableSimulators', 'filteredSimulator'));
    }
}
