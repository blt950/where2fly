<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{

    /**
     * Handle a registration request for the application.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request){
        $data = $request->validate([
            'username' => ['required', 'string', 'max:64'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed'],
        ]);

        // Hash the password
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        if($user){
            return redirect()->route('front')->with('success', 'Your account has been created. Check your email to verify your account.');
        } else {
            return redirect()->route('front')->with('error', 'An error occurred while creating your account. Please try again later.');
        }
    }
}