<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public static function login(Request $request, User $user)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $request->username)->first();

        // Check if the user exists and the password is correct
        if($user && Hash::check($request->password, $user->password)){
            Auth::login($user);
            return redirect()->route('front')->with('success', 'You have been logged in.');
        } else {
            return back()->with('error', 'Invalid login credentials. Please try again.');
        }
    }

    public static function logout(){
        Auth::logout();
        return redirect()->route('front')->with('success', 'You have been logged out.');
    }
}
