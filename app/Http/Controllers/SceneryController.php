<?php

namespace App\Http\Controllers;

use App\Models\Airport;
use App\Models\Scenery;
use App\Models\Simulator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SceneryController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $simulators = Simulator::all();

        return view('scenery.create', compact('simulators'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'icao' => 'required|max:4|min:4|exists:airports,icao',
            'author' => 'required|string',
            'link' => 'required|url',
            'simulator' => 'required|exists:simulators,id',
            'payware' => 'boolean',
        ]);

        $scenery = new Scenery();
        $scenery->icao = strtoupper($request->icao);
        $scenery->author = $request->author;
        $scenery->link = $request->link;
        $scenery->airport_id = Airport::where('icao', $request->icao)->get()->first()->id;
        $scenery->simulator_id = $request->simulator;
        $scenery->payware = $request->payware ? true : false;
        $scenery->published = false;
        $scenery->suggested_by_user_id = Auth::id();
        $scenery->save();

        return redirect()->route('scenery.create')->with('success', 'Thank you for your contribution. We will review it and add it to the database if it meets our criteria.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Scenery $scenery)
    {
        $this->authorize('update', $scenery);

        $simulators = Simulator::all();
        $suggestedByUser = $scenery->suggestedByUser;

        return view('scenery.edit', compact('scenery', 'simulators', 'suggestedByUser'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Scenery $scenery)
    {
        $this->authorize('update', $scenery);

        $request->validate([
            'icao' => 'required|max:4|min:4|exists:airports,icao',
            'author' => 'required|string',
            'link' => 'required|url',
            'simulator' => 'required|exists:simulators,id',
            'payware' => 'boolean',
            'published' => 'boolean',
        ]);

        $scenery->icao = strtoupper($request->icao);
        $scenery->author = $request->author;
        $scenery->link = $request->link;
        $scenery->airport_id = Airport::where('icao', $request->icao)->get()->first()->id;
        $scenery->simulator_id = $request->simulator;
        $scenery->payware = $request->payware ? true : false;
        $scenery->published = $request->published ? true : false;
        $scenery->suggested_by_user_id = ($scenery->suggested_by_user_id) ? $scenery->suggested_by_user_id : Auth::id();
        $scenery->save();

        return redirect()->route('admin')->with('success', 'Scenery updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Scenery $scenery)
    {
        $this->authorize('delete', $scenery);

        $scenery->delete();

        return redirect()->route('admin')->with('success', 'Scenery deleted successfully.');
    }
}
