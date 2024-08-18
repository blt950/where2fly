<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public static function login(User $user)
    {
        Auth::login($user);
    }

    public function logout(){
        Auth::logout();
        return redirect()->route('front')->with('success', 'You have been logged out.');
    }
}
