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
                    $userLists = UserList::where('user_id', Auth::id())->with('airports', 'airports.metar', 'airports.runways')->get();
                    $airportsMapCollection = MapHelper::getAirportsFromUserLists($userLists);
                    $airportMapData = MapHelper::generateAirportMapDataFromAirports($airportsMapCollection);

                    $airports = collect($airportsMapCollection);
                    $sceneriesCollection = Scenery::where('published', true)->whereIn('airport_id', collect($airports)->pluck('id'))->with('simulator')->get();

                    $view->with('airportsMapCollection', $airportsMapCollection);
                    $view->with('airportMapData', $airportMapData);

                    $view->with('sceneriesCollection', $sceneriesCollection);
                    $view->with('airports', $airports);
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
