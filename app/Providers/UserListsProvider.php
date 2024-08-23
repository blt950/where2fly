<?php

namespace App\Providers;

use App\Helpers\MapHelper;
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
                if (! $view->offsetExists('airportsMapCollection')) {
                    $airportsMapCollection = Auth::user()->getAirportsFromLists();
                    $view->with('airportsMapCollection', $airportsMapCollection);
                }

                if (! $view->offsetExists('airportMapData')) {
                    $airportMapData = MapHelper::generateAirportMapDataFromUserLists(Auth::user());
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
