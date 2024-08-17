<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * Handle a registration request for the application.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:64', 'unique:' . User::class],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed'],
        ]);

        //
        //
        //
        // Let's move this to UserController instead? That'd make more sense.

        // Hash the password
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        if ($user) {
            event(new Registered($user));
            $this->login($user);

            return redirect()->route('front')->with('success', 'Your account has been created. Check your email to verify your account.');
        } else {
            return redirect()->route('front')->with('error', 'An error occurred while creating your account. Please try again later.');
        }
    }

    public function login(User $user)
    {
        Auth::login($user);
    }
}
