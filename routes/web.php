<?php

use App\Http\Controllers\SearchController;
use App\Http\Controllers\TopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [SearchController::class, 'indexArrivalSearch'])->name('front');
Route::get('/departures/', [SearchController::class, 'indexDepartureSearch'])->name('front.departures');
Route::get('/routes/', [SearchController::class, 'indexRouteSearch'])->name('front.routes');

Route::get('/top', [TopController::class, 'index'])->name('top');
Route::get('/top/{continent}', [TopController::class, 'index'])->name('top.filtered');

// Pure views
Route::view('/changelog', 'changelog')->name('changelog');
Route::view('/privacy', 'privacy')->name('privacy');
Route::view('/api', 'api')->name('api');

// Search
Route::post('/search', [SearchController::class, 'search'])->name('search');
Route::post('/search/routes', [SearchController::class, 'searchRoutes'])->name('search.routes');

Route::get('/search', function () {
    return redirect(route('front'));
});

// Old routes
Route::permanentRedirect('/advanced', '/');
Route::permanentRedirect('/advanced/search', '/');
