<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportContentXmlRequest;
use App\Models\Airport;
use App\Models\Simulator;
use App\Models\UserList;
use App\Services\Msfs\AirportCodeLookup;
use App\Services\Msfs\ContentXmlException;
use App\Services\Msfs\ContentXmlReader;
use App\Services\Msfs\SceneryPackageClassifier;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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
        $lists = UserList::where('user_id', Auth::id())->with('simulator')->withCount('airports')->get()->sortBy('name');

        return Inertia::render('Lists', [
            'lists' => $lists->values()->map(fn (UserList $list) => [
                'id' => $list->id,
                'name' => $list->name,
                'color' => $list->color,
                'simulator' => $list->simulator?->shortened_name,
                'airportsCount' => $list->airports_count,
                'hidden' => (bool) $list->hidden,
            ]),
        ])->withViewData(['title' => 'My Lists']);
    }

    /**
     * Show the form for creating a new list.
     *
     * @return Response
     */
    public function create(Request $request)
    {
        return $this->renderForm($request, null);
    }

    /**
     * Store a newly created list in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $this->validateList($request);

        [$airportIds, $notFoundAirports] = $this->resolveAirports($request->airports);

        DB::transaction(function () use ($request, $airportIds) {
            $list = UserList::create([
                'color' => $request->color,
                'name' => $request->name,
                'simulator_id' => $request->simulator,
                'user_id' => Auth::id(),
                'public' => $request->boolean('public'),
            ]);

            $list->airports()->sync($airportIds);
        });

        return $this->listSavedRedirect('created', $notFoundAirports);
    }

    /**
     * Show the form for editing the list
     *
     * @return Response
     */
    public function edit(UserList $list)
    {
        $this->authorize('update', $list);

        return $this->renderForm(request(), $list);
    }

    /**
     * Update the list
     */
    public function update(Request $request, UserList $list)
    {
        $this->authorize('update', $list);
        $this->validateList($request);

        [$airportIds, $notFoundAirports] = $this->resolveAirports($request->airports);

        DB::transaction(function () use ($request, $list, $airportIds) {
            $list->color = $request->color;
            $list->name = $request->name;
            $list->simulator_id = $request->simulator;
            $list->public = $request->boolean('public');
            $list->save();

            $list->airports()->sync($airportIds);
            // A change to the airports alone leaves the row clean; the map's lists revision needs it to move
            $list->touch();
        });

        return $this->listSavedRedirect('updated', $notFoundAirports);
    }

    /**
     * Suggest a list from an uploaded MSFS 2024 Content.xml. The file is read in place and never stored.
     */
    public function importMsfs(ImportContentXmlRequest $request, ContentXmlReader $reader, AirportCodeLookup $lookup): JsonResponse
    {
        try {
            $packages = $reader->read($request->file('content_xml')->getRealPath(), config('msfs_scenery.max_upload_kb') * 1024);
        } catch (ContentXmlException $e) {
            return response()->json(['message' => $e->getMessage(), 'reason' => $e->reason], 422);
        }

        $classifier = new SceneryPackageClassifier(config('msfs_scenery'), $lookup(...));

        return response()->json($classifier->classify($packages, $request->boolean('include_native')));
    }

    /**
     * The create and edit form. A failed save comes back here with the input as old input.
     */
    private function renderForm(Request $request, ?UserList $list)
    {
        return Inertia::render('ListForm', [
            'list' => $list ? [
                'id' => $list->id,
                'name' => $list->name,
                'color' => $list->color,
                'simulator' => $list->simulator_id,
                'public' => (bool) $list->public,
                'airports' => $list->airports->pluck('icao')->sort()->implode("\r\n"),
            ] : null,
            'simulators' => Simulator::all()->sortBy('order')->values()->map(fn ($simulator) => ['id' => $simulator->id, 'name' => $simulator->name]),
            'canMakePublic' => $request->user()->can('public', UserList::class),
            'old' => (object) $request->session()->getOldInput(),
        ])->withViewData(['title' => $list ? 'Edit list' : 'Your lists']);
    }

    /**
     * Shared store/update validation; going public needs its own permission
     */
    private function validateList(Request $request): void
    {
        $request->validate([
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'name' => 'required|max:32',
            'simulator' => 'required|exists:simulators,id',
            'airports' => 'required|string',
            'public' => 'boolean',
        ]);

        if ($request->boolean('public')) {
            $this->authorize('public', UserList::class);
        }
    }

    private function listSavedRedirect(string $action, Collection $notFoundAirports)
    {
        if ($notFoundAirports->isNotEmpty()) {
            return redirect()->route('list.index')->with('warning', "List {$action} successfully, however following airports could not be found: " . $notFoundAirports->implode(', '));
        }

        return redirect()->route('list.index')->with('success', "List {$action} successfully");
    }

    /**
     * Resolve airport inputs into unique airport IDs and unknown inputs.
     *
     * @return array{0: array<int, int>, 1: Collection}
     */
    private function resolveAirports(string $input): array
    {
        // Any line ending: a native form posts \r\n, the Inertia form posts \n
        $airportsInput = collect(preg_split('/\r\n|\r|\n/', $input))
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
