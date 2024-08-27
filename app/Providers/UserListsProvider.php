<?php

namespace App\Providers;

use App\Helpers\MapHelper;
use App\Models\Scenery;
use App\Models\UserList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class UserListsProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        View::composer('*', function ($view) {
            if (Auth::check()) {
                if (! $view->offsetExists('airportsMapCollection') && ! $view->offsetExists('airportMapData') && ! $view->offsetExists('sceneriesCollection')) {
                    $userLists = UserList::where('user_id', Auth::id())
                        ->with(['airports' => function ($query) {
                            $query->take(501)
                                ->with('metar', 'runways');
                        }])
                        ->get();

                    $airportsMapCollection = MapHelper::getAirportsFromUserLists($userLists);
                    $airportMapData = MapHelper::generateAirportMapDataFromAirports($airportsMapCollection);

                    $airports = collect($airportsMapCollection);
                    $sceneriesCollection = Scenery::where('published', true)
                        ->whereIn('airport_id', collect($airports)->pluck('id'))
                        ->with('simulator')
                        ->orderBy('payware', 'desc')
                        ->orderBy('author', 'asc')
                        ->get();

                    $view->with('airportsMapCollection', $airportsMapCollection);
                    $view->with('airportMapData', $airportMapData);

                    $view->with('sceneriesCollection', $sceneriesCollection);
                    $view->with('airports', $airports);

                    // TODO remove this and introduce eager loading instead
                    // Issue a warning if a user has more than 500 airports in a list?
                    foreach ($userLists as $list) {
                        if ($list->airports->count() > 500) {
                            $view->with('warningAirportListAmount', true);
                        }
                    }
                }
            }
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
