<?php

use App\Http\Controllers\API\MapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => ['api-token']], function () {
    Route::get('/top', [App\Http\Controllers\API\TopController::class, 'index'])->name('api.top.index');
    Route::post('/top', [App\Http\Controllers\API\TopController::class, 'indexWhitelist'])->name('api.top.index.whitelist');
    Route::post('/search', [App\Http\Controllers\API\SearchController::class, 'search'])->name('api.search');
});

Route::get('/user/authenticated', [App\Http\Controllers\API\MapController::class, 'isAuthenticated'])->name('api.user.authenticated');

Route::post('/airport', [MapController::class, 'getAirport'])->name('api.airport.show');
Route::post('/flights', [MapController::class, 'getFlights'])->name('api.airport.flights');
Route::post('/scenery', [MapController::class, 'getScenery'])->name('api.airport.scenery');
Route::post('/mapdata/icao', [MapController::class, 'getMapdataFromIcao'])->name('api.mapdata.icao');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/lists/airports', [MapController::class, 'getListAirports'])->name('api.lists.airports');
});
