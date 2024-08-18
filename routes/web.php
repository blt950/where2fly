<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TopController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
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

// User account related routes
Route::view('/login', 'account.login')->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('user.login');

Route::view('/register', 'account.register')->name('register');
Route::post('/register', [UserController::class, 'store'])->name('user.register');

Route::view('/account/reset', 'account.reset')->name('user.reset');
Route::post('/account/reset', [UserController::class, 'reset'])->middleware('guest')->name('user.reset.post');
Route::view('/reset-password', 'account.reset-password')->name('password.request');
Route::post('/reset-password', [UserController::class, 'showResetForm'])->middleware('guest')->name('password.update');
Route::get('/reset-password/{token}', function (string $token) {
    return view('account.reset-password', ['token' => $token]);
})->middleware('guest')->name('password.reset');

Route::get('/logout', [LoginController::class, 'logout'])->name('user.logout');
Route::get('/account', [UserController::class, 'show'])->middleware(['auth', 'verified'])->name('user.account');

// User account related emails
Route::get('/email/verify', [UserController::class, 'verificationNotice'])->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [UserController::class, 'verifyEmail'])->middleware(['auth', 'signed'])->name('verification.verify');
Route::post('/email/verification-notification', [UserController::class, 'veryEmailResend'])->middleware(['auth', 'throttle:1,10'])->name('verification.send');

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
