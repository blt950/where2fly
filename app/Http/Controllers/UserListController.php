<?php

namespace App\Http\Controllers;

use App\Models\Airport;
use App\Models\Simulator;
use App\Models\UserList;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

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
        $simulators = Simulator::all()->sortBy('order');

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

        $notFoundAirports = $this->processAirports($list, $request->airports);

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
        $simulators = Simulator::all()->sortBy('order');

        // Use the manual umami tracking script on this page
        View::share('manualTracking', true);

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

        $list->airports()->detach();
        $notFoundAirports = $this->processAirports($list, $request->airports);

        if (count($notFoundAirports) > 0) {
            return redirect()->route('list.index')->with('warning', 'List updated successfully, however following airports could not be found: ' . $notFoundAirports->implode(', '));
        }

        return redirect()->route('list.index')->with('success', 'List updated successfully');
    }

    /**
     * Process the airports and save the models
     *
     * @return \Illuminate\Support\Collection $notFoundAirports
     */
    private function processAirports(UserList $list, string $input)
    {
        $airportsInput = explode("\r\n", $input);
        $airportsInput = array_map('strtoupper', $airportsInput);
        $airportsInput = array_filter($airportsInput, function ($value) {
            return ! empty($value);
        });

        $airportModels = Airport::whereIn('icao', $airportsInput)
            ->orWhereIn('local_code', $airportsInput)
            ->get();

        $addedAirports = collect();
        $notFoundAirports = collect();
        $airportsToAttach = collect();

        foreach ($airportsInput as $airportInput) {
            // Skip if we already added it
            if ($addedAirports->contains($airportInput)) {
                continue;
            }

            $airportModel = $airportModels->first(function ($model) use ($airportInput) {
                return $model->icao === $airportInput || $model->local_code === $airportInput;
            });

            if ($airportModel) {
                $airportsToAttach->push($airportModel);
                $addedAirports->push($airportInput);
            } else {
                $notFoundAirports->push($airportInput);
            }
        }

        if (! $airportsToAttach->isEmpty()) {
            $list->airports()->attach($airportsToAttach->pluck('id'));
        }

        return $notFoundAirports;
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

    /**
     * Toggle the list visibility
     *
     * @return \Illuminate\Http\Response
     */
    public function toggle(UserList $list)
    {
        $this->authorize('update', $list);
        $list->hidden = ! $list->hidden;
        $list->save();

        return redirect()->route('list.index');
    }
}
