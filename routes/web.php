<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TopController;
use App\Http\Controllers\UserController;
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

// SearchController
Route::controller(SearchController::class)->group(function () {
    Route::get('/', 'indexArrivalSearch')->name('front');
    Route::get('/departures/', 'indexDepartureSearch')->name('front.departures');
    Route::get('/routes/', 'indexRouteSearch')->name('front.routes');

    Route::post('/search', 'search')->name('search');
    Route::post('/search/routes', 'searchRoutes')->name('search.routes');
});

// TopController
Route::controller(TopController::class)->group(function () {
    Route::get('/top', 'index')->name('top');
    Route::get('/top/{continent}', 'index')->name('top.filtered');
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
    Route::middleware('guest')->group(function () {
        Route::get('/account/recovery', 'resetRequestForm')->name('account.recovery');
        Route::post('/account/recovery', 'resetSendLink');
        Route::get('/account/recovery/reset/{token}', 'resetForm')->name('password.reset');
        Route::post('/account/recovery/reset/', 'resetPassword')->name('password.update');
    });

    // Account pages
    Route::get('/account', 'show')->middleware(['auth', 'verified'])->name('user.account');
    Route::post('/account/delete', 'destroy')->name('user.delete');

    // Account email verification
    Route::get('/account/verify/', 'verifyNotice')->name('verification.notice');
    Route::get('/account/verify/{id}/{hash}', 'verifyEmail')->middleware(['auth', 'signed'])->name('verification.verify');
    Route::post('/account/verify/resend', 'verifyResendEmail')->middleware(['auth', 'throttle:1,10'])->name('verification.send');
});

// Pure views
Route::view('/changelog', 'changelog')->name('changelog');
Route::view('/privacy', 'privacy')->name('privacy');
Route::view('/api', 'api')->name('api');

// Redirects
Route::get('/search', function () {
    return redirect(route('front'));
});

// Old routes
Route::permanentRedirect('/advanced', '/');
Route::permanentRedirect('/advanced/search', '/');
