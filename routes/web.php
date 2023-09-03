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

Route::get('/', [SearchController::class, 'index'])->name('front');
Route::get('/advanced', function () {
    return redirect(route('front'));
});

Route::get('/top', [TopController::class, 'index'])->name('top');
Route::get('/top/{continent}', [TopController::class, 'index'])->name('top.filtered');

Route::get('/changelog', function (){
    return view('changelog');
})->name('changelog');

Route::get('/privacy', function (){
    return view('privacy');
})->name('privacy');

Route::post('/search', [SearchController::class, 'search'])->name('search');

// Failsafe if you try to access the search page directly
Route::get('/search', function () {
    return redirect(route('front'));
});

Route::get('/advanced/search', function () {
    return redirect(route('front.advanced'));
});