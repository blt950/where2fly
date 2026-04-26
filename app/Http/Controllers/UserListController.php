<?php

namespace App\Http\Controllers;

use App\Models\Airport;
use App\Models\Simulator;
use App\Models\UserList;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class UserListController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $lists = UserList::where('user_id', Auth::id())->withCount('airports')->get()->sortBy('name');

        return view('list.index', compact('lists'));
    }

    /**
     * Show the form for creating a new list.
     *
     * @return Response
     */
    public function create(Request $request)
    {
        $simulators = Simulator::all()->sortBy('order');

        return view('list.create', compact('simulators'));
    }

    /**
     * Store a newly created list in storage.
     *
     * @return Response
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

        $request->public = (bool) $request->public;
        if ($request->public) {
            $this->authorize('public', UserList::class);
        }

        [$airportIds, $notFoundAirports] = $this->resolveAirports($request->airports);

        $list = DB::transaction(function () use ($request, $airportIds) {
            $list = new UserList();
            $list->color = $request->color;
            $list->name = $request->name;
            $list->simulator_id = $request->simulator;
            $list->user_id = Auth::id();
            $list->public = $request->public;
            $list->save();

            $list->airports()->sync($airportIds);

            return $list;
        });

        if (count($notFoundAirports) > 0) {
            return redirect()->route('list.index')->with('warning', 'List created successfully, however following airports could not be found: ' . $notFoundAirports->implode(', '));
        }

        return redirect()->route('list.index')->with('success', 'List created successfully');
    }

    /**
     * Show the form for editing the list
     *
     * @return Response
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

        $request->public = (bool) $request->public;
        if ($request->public) {
            $this->authorize('public', UserList::class);
        }

        [$airportIds, $notFoundAirports] = $this->resolveAirports($request->airports);

        DB::transaction(function () use ($request, $list, $airportIds) {
            $list->color = $request->color;
            $list->name = $request->name;
            $list->simulator_id = $request->simulator;
            $list->public = $request->public;
            $list->save();

            $list->airports()->sync($airportIds);
        });

        if (count($notFoundAirports) > 0) {
            return redirect()->route('list.index')->with('warning', 'List updated successfully, however following airports could not be found: ' . $notFoundAirports->implode(', '));
        }

        return redirect()->route('list.index')->with('success', 'List updated successfully');
    }

    /**
     * Resolve airport inputs into unique airport IDs and unknown inputs.
     *
     * @return array{0: array<int, int>, 1: Collection}
     */
    private function resolveAirports(string $input): array
    {
        $airportsInput = collect(explode("\r\n", $input))
            ->map(fn ($v) => strtoupper(trim($v)))
            ->filter()
            ->all();

        $airportModels = Airport::whereIn('icao', $airportsInput)
            ->orWhereIn('local_code', $airportsInput)
            ->get();

        $notFoundAirports = collect();
        $airportIdsToAttach = [];

        foreach ($airportsInput as $airportInput) {
            $airportModel = $airportModels->first(function ($model) use ($airportInput) {
                return $model->icao === $airportInput || $model->local_code === $airportInput;
            });

            if ($airportModel) {
                $airportIdsToAttach[$airportModel->id] = $airportModel->id;
            } else {
                $notFoundAirports->push($airportInput);
            }
        }

        return [array_values($airportIdsToAttach), $notFoundAirports];
    }

    /**
     * Remove the list
     *
     * @return Response
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
     * @return Response
     */
    public function toggle(UserList $list)
    {
        $this->authorize('update', $list);
        $list->hidden = ! $list->hidden;
        $list->save();

        return redirect()->route('list.index');
    }
}
