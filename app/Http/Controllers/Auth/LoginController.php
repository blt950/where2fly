<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class LoginController extends Controller
{
    /**
     * Handle a login request for the application.
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request, User $user)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
            'cf-turnstile-response' => ['required', Rule::turnstile()],
        ]);

        $user = User::where('username', $request->username)->orWhere('email', $request->username)->first();
        $remember = ($request->remember) ? true : false;

        // Check if the user exists and the password is correct
        if ($user && Hash::check($request->password, $user->password)) {

            if ($remember) {
                Auth::login($user, true);
            } else {
                Auth::login($user);
            }

            // Route to the original url the user was trying to access before promoted with login
            if ($request->session()->has('url.intended')) {
                return redirect()->intended()->with('success', 'You have been logged in.');
            }

            return redirect()->route('front')->with('success', 'You have been logged in.');
        } else {
            return back()->with('error', 'Invalid login credentials. Please try again.');
        }
    }

    /**
     * Handle a logout request for the application.
     *
     * @return \Illuminate\Http\Response
     */
    public static function logout()
    {
        Auth::logout();

        return redirect()->route('front')->with('success', 'You have been logged out.');
    }

    /**
     * Show the registration form
     *
     * @return \Illuminate\Http\Response
     */
    public function showRegister()
    {
        return view('account.register');
    }

    /**
     * Show the login form
     *
     * @return \Illuminate\Http\Response
     */
    public function showLogin()
    {
        return view('account.login');
    }
}
