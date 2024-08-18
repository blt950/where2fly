<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class UserController extends Controller
{
    /**
     * Handle a registration request for the application.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:2', 'max:64', 'unique:' . User::class],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        // Hash the password
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        if ($user) {
            event(new Registered($user));
            LoginController::login($user);

            return redirect()->route('front')->with('success', 'Your account has been created. Check your email to verify your account.');
        } else {
            return redirect()->route('front')->with('error', 'An error occurred while creating your account. Please try again later.');
        }
    }

    public function verificationNotice(Request $request)
    {
        if ($request->session()->previousUrl() === route('register')) {
            return redirect()->route('front')->with('success', 'Your account has been created. Check your email to verify your account.');
        } else {
            return redirect()->route('front')->with('warning', 'Please verify your account first. Check your email for the link.');
        }
    }

    public function verifyEmail(EmailVerificationRequest $request){
        $request->fulfill();
        return redirect()->route('front')->with('success', 'Your email has been verified.');
    }

    public function veryEmailResend(Request $request){
        $request->user()->sendEmailVerificationNotification();
        return back()->with('success', 'Verification link sent! Check your email to verify your account.');
    }

    public function show(){
        return view('account.profile');
    }
}
