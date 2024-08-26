<?php

namespace App\Http\Controllers;

use App\Models\Airport;
use App\Models\Simulator;
use App\Models\UserList;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserListController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $lists = UserList::where('user_id', Auth::id())->withCount('airports')->get();

        return view('list.index', compact('lists'));
    }

    /**
     * Show the form for creating a new list.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $simulators = Simulator::all();

        return view('list.create', compact('simulators'));
    }

    /**
     * Store a newly created list in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'name' => 'required|max:32',
            'simulator' => 'required|exists:simulators,id',
            'airports' => 'required|string',
            'public' => 'boolean',
        ]);

        $request->public = $request->public ? true : false;
        if ($request->public) {
            $this->authorize('public', UserList::class);
        }

        $list = new UserList();
        $list->color = $request->color;
        $list->name = $request->name;
        $list->simulator_id = $request->simulator;
        $list->user_id = Auth::id();
        $list->public = $request->public;
        $list->save();

        // Explode based on line breaks and attach each airport to the list
        $airports = explode("\r\n", $request->airports);
        $addedAirports = collect();
        $notFoundAirports = collect();
        foreach ($airports as $airport) {
            // Skip if we already added it
            if ($addedAirports->contains($airport)) {
                continue;
            }

            $airportModel = Airport::where('icao', strtoupper($airport))->first();
            if ($airportModel) {
                $list->airports()->attach($airportModel);
                $addedAirports->push($airport);
            } else {
                $notFoundAirports->push($airport);
            }
        }

        if (count($notFoundAirports) > 0) {
            return redirect()->route('list.index')->with('warning', 'List created successfully, however following airports could not be found: ' . $notFoundAirports->implode(', '));
        }

        return redirect()->route('list.index')->with('success', 'List created successfully');
    }

    /**
     * Show the form for editing the list
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(UserList $list)
    {
        $this->authorize('update', $list);
        $simulators = Simulator::all();

        return view('list.edit', compact('list', 'simulators'));
    }

    /**
     * Update the list
     */
    public function update(Request $request, UserList $list)
    {
        $this->authorize('update', $list);
        $request->validate([
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'name' => 'required|max:32',
            'simulator' => 'required|exists:simulators,id',
            'airports' => 'required|string',
            'public' => 'boolean',
        ]);

        $request->public = $request->public ? true : false;
        if ($request->public) {
            $this->authorize('public', UserList::class);
        }

        $list->color = $request->color;
        $list->name = $request->name;
        $list->simulator_id = $request->simulator;
        $list->public = $request->public;
        $list->save();

        // Explode based on line breaks and attach each airport to the list
        $airports = explode("\r\n", $request->airports);
        $notFoundAirports = collect();
        $addedAirports = collect();
        $list->airports()->detach();
        foreach ($airports as $airport) {
            // Skip if we already added it
            if ($addedAirports->contains(strtoupper($airport))) {
                continue;
            }

            $airportModel = Airport::where('icao', strtoupper($airport))->first();
            if ($airportModel) {
                $list->airports()->attach($airportModel);
                $addedAirports->push($airport);
            } else {
                $notFoundAirports->push($airport);
            }
        }

        if (count($notFoundAirports) > 0) {
            return redirect()->route('list.index')->with('warning', 'List updated successfully, however following airports could not be found: ' . $notFoundAirports->implode(', '));
        }

        return redirect()->route('list.index')->with('success', 'List updated successfully');
    }

    /**
     * Remove the list
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserList $list)
    {
        $this->authorize('delete', $list);
        $list->delete();

        return redirect()->route('list.index')->with('success', 'List deleted successfully');
    }
}
