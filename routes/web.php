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
Route::get('/advanced', [SearchController::class, 'indexAdvanced'])->name('front.advanced');

Route::get('/top', [TopController::class, 'index'])->name('top');
Route::get('/top/{continent}', [TopController::class, 'index'])->name('top.filtered');

Route::post('/search', [SearchController::class, 'search'])->name('search');
Route::post('/advanced/search', [SearchController::class, 'searchAdvanced'])->name('search.advanced');