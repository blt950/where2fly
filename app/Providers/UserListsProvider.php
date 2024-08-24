<?php

namespace App\Providers;

use App\Helpers\MapHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\UserList;

class UserListsProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        View::composer('*', function ($view) {
            if (Auth::check()) {
                if (! $view->offsetExists('airportsMapCollection') && ! $view->offsetExists('airportMapData')) {
                    $userLists = UserList::where('user_id', Auth::id())->with('airports', 'airports.metar', 'airports.runways')->get();
                    $airportsMapCollection = MapHelper::getAirportsFromUserLists($userLists);
                    $airportMapData = MapHelper::generateAirportMapDataFromAirports($airportsMapCollection);
                    $view->with('airportsMapCollection', $airportsMapCollection);
                    $view->with('airportMapData', $airportMapData);
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
