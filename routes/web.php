<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SceneryController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TopController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserListController;
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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::controller(UserListController::class)->group(function () {
        Route::get('/lists', 'index')->name('list.index');
        Route::get('/lists/create', 'create')->name('list.create');
        Route::post('/lists/create', 'store')->name('list.store');
        Route::get('/lists/{list}/edit', 'edit')->name('list.edit');
        Route::post('/lists/{list}/edit', 'update')->name('list.update');
        Route::get('/lists/{list}/delete', 'destroy')->name('list.delete');
        Route::get('/lists/{list}/toggle', 'toggle')->name('list.toggle');
    });

    Route::controller(SceneryController::class)->group(function () {
        Route::get('/scenery/create', 'create')->name('scenery.create');
        Route::post('/scenery/create', 'store')->name('scenery.store');
        Route::get('/scenery/{scenery}/edit', 'edit')->name('scenery.edit');
        Route::post('/scenery/{scenery}/edit', 'update')->name('scenery.update');
        Route::get('/scenery/{scenery}/delete', 'destroy')->name('scenery.delete');
    });
});

// SearchController
Route::controller(SearchController::class)->group(function () {
    Route::get('/', 'indexArrivalSearch')->name('front'); // If you change this path, remember correcting it in searchForm.js for metrics as well
    Route::get('/departures/', 'indexDepartureSearch')->name('front.departures'); // If you change this path, remember correcting it in searchForm.js for metrics as well
    Route::get('/routes/', 'indexRouteSearch')->name('front.routes');

    Route::post('/search', 'search')->name('search');
    Route::post('/search/routes', 'searchRoutes')->name('search.routes');
});

// TopController
Route::controller(TopController::class)->group(function () {
    Route::get('/top', 'index')->name('top');
    Route::get('/top/{continent}', 'index')->name('top.filtered');
});

// Scenery
Route::controller(SceneryController::class)->group(function () {
    Route::get('/scenery', 'indexAirports')->name('scenery');
    Route::get('/scenery/{simulator}', 'indexAirports')->name('scenery.filtered');
});

// User account related routes
Route::controller(LoginController::class)->group(function () {
    Route::get('/register', 'showRegister')->name('register');
    Route::get('/login', 'showLogin')->name('login');
    Route::post('/login', 'login')->name('user.login');
    Route::get('/logout', 'logout')->name('user.logout');
});

// User account related routes
Route::controller(UserController::class)->group(function () {

    // Account creation
    Route::post('/register', 'store')->middleware('guest')->name('user.register');

    // Account reset
    Route::get('/account/recovery', 'resetRequestForm')->name('account.recovery');
    Route::post('/account/recovery', 'resetSendLink');
    Route::get('/account/recovery/reset/{token}', 'resetForm')->name('password.reset');
    Route::post('/account/recovery/reset/', 'resetPassword')->name('password.update');

    // Account pages
    Route::get('/account', 'show')->middleware(['auth', 'verified'])->name('user.account');
    Route::post('/account/delete', 'destroy')->name('user.delete');

    // Account email verification
    Route::get('/account/verify/', 'verifyNotice')->name('verification.notice');
    Route::get('/account/verify/{id}/{hash}', 'verifyEmail')->middleware(['auth', 'signed'])->name('verification.verify');
    Route::post('/account/verify/resend', 'verifyResendEmail')->middleware(['auth', 'throttle:1,10,user'])->name('verification.send');

    // Admin
    Route::get('/admin', 'showAdmin')->middleware(['auth', 'verified'])->name('admin');
});

// Pure views
Route::view('/changelog', 'changelog')->name('changelog');
Route::view('/privacy', 'privacy')->name('privacy');
Route::view('/api', 'api')->name('api');
Route::view('/donate', 'donate')->name('donate');

// Redirects
Route::get('/search', function () {
    return redirect(route('front'));
});

// Old routes
Route::permanentRedirect('/advanced', '/');
Route::permanentRedirect('/advanced/search', '/');
