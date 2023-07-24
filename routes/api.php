<?php

use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['api-token']], function () {
    Route::get('/top', [App\Http\Controllers\API\TopController::class, 'index'])->name('api.top.index');
    Route::post('/top', [App\Http\Controllers\API\TopController::class, 'indexWhitelist'])->name('api.top.index.whitelist');
    Route::post('/search', [App\Http\Controllers\API\SearchController::class, 'search'])->name('api.search');
});