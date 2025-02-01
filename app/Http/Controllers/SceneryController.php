<?php

namespace App\Http\Controllers;

use App\Helpers\MapHelper;
use App\Models\Airport;
use App\Models\Scenery;
use App\Models\Simulator;
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
        $simulators = Simulator::all()->sortBy('order');
        $airport = Airport::where('icao', $request->get('airport'))->first();
        $sceneries = null;

        if ($airport) {
            $sceneries = $airport->sceneries;
        }

        return view('scenery.create', compact('simulators', 'sceneries'));
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

        $scenery = new Scenery();
        $scenery->icao = strtoupper($request->icao);
        $scenery->developer = $request->developer;
        $scenery->airport_id = Airport::where('icao', $request->icao)->get()->first()->id;
        $scenery->save();

        // Attach the simulator to the scenery
        if ($request->simulators) {
            $scenery->simulators()->attach($request->simulators, [
                'link' => $request->link,
                'payware' => $request->payware ? true : false,
                'published' => false,
                'source' => 'user',
                'suggested_by_user_id' => Auth::id(),
            ]);
        }

        return redirect()->route('scenery.create')->with('success', 'Thank you for your contribution. We will review it and add it to the database if it meets our criteria.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Scenery $scenery, Simulator $simulator)
    {
        $this->authorize('update', $scenery);

        $availableSimulators = Simulator::all()->sortBy('order');

        $sceneryEntry = $scenery->simulators->where('id', $simulator->id)->first();
        $suggestedByUser = $sceneryEntry->pivot->suggestedByUser;

        $existingSceneries = $scenery->airport->sceneries;

        // Use the manual umami tracking script on this page
        View::share('manualTracking', true);

        return view('scenery.edit', compact('scenery', 'simulator', 'availableSimulators', 'existingSceneries', 'suggestedByUser'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Scenery $scenery, Simulator $simulator)
    {
        $this->authorize('update', $scenery);

        $request->validate([
            'icao' => 'required|max:4|min:4|exists:airports,icao',
            'developer' => 'required|string',
            'link' => 'required|url',
            'payware' => 'required|boolean',
            'published' => 'boolean',
        ]);

        $scenery->icao = strtoupper($request->icao);
        $scenery->developer = $request->developer;
        $scenery->save();

        // Update the pivot table simulators with the new data
        $scenery->simulators()->updateExistingPivot($simulator->id, [
            'link' => $request->link,
            'payware' => $request->payware ? true : false,
            'published' => $request->published ? true : false,
            'source' => 'user',
            'suggested_by_user_id' => Auth::id(),
        ]);

        return redirect()->route('admin')->with('success', 'Scenery updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Scenery $scenery, Simulator $simulator)
    {
        $this->authorize('delete', $scenery);
        $scenery->simulators()->detach($simulator->id);

        return redirect()->route('admin')->with('success', 'Scenery deleted successfully.');
    }

    /**
     * Index of all airports with scenery
     */
    public function indexAirports(Request $request, ?string $filteredSim = null)
    {
        $simulators = Simulator::whereHas('sceneries')->get();
        $filteredSimulator = $simulators->where('shortened_name', $filteredSim)->first();

        if ($filteredSimulator) {
            $airports = $filteredSimulator->sceneries->where('published', true)->pluck('airport_id');
            $airports = Airport::whereIn('id', $airports)->get();
        } else {
            $airports = Airport::whereHas('sceneries', function ($query) {
                $query->where('published', true);
            })->get();
        }

        $airportMapData = json_encode(MapHelper::generateAirportMapDataFromAirports($airports));
        $airportsCount = $airports->count();

        return view('scenery', compact('airportsCount', 'airportMapData', 'simulators', 'filteredSimulator'));
    }
}
