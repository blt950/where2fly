<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
            'username' => ['required', 'string', 'min:2', 'max:32', 'unique:' . User::class],
            'email' => ['required', 'string', 'lowercase', 'email', 'indisposable', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', 'min:8', 'max:255'],
            'cf-turnstile-response' => ['required', Rule::turnstile()],
        ]);

        // Hash the password
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        if ($user) {
            event(new Registered($user));
            Auth::login($user);

            return redirect()->route('front')->with('success', 'Your account has been created. Check your email to verify your account.');
        } else {
            return redirect()->route('front')->with('error', 'An error occurred while creating your account. Please try again later.');
        }
    }

    /**
     * Show the user profile
     *
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        return view('account.settings');
    }

    /**
     * Handle a user deletion request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $user = $request->user();
        $user->delete();

        return redirect()->route('front')->with('success', 'Your account has been deleted.');
    }

    /**
     * Verification process: Show a verification notice to the user based on the previous URL
     *
     * @return \Illuminate\Http\Response
     */
    public function verifyNotice(Request $request)
    {
        if ($request->session()->previousUrl() === route('register')) {
            return redirect()->route('front')->with('success', 'Your account has been created. Check your email to verify your account.');
        } else {
            return redirect()->route('front')->with('error', 'Please verify your account first.');
        }
    }

    /**
     * Verification process: Verify the email and redirect the user
     *
     * @return \Illuminate\Http\Response
     */
    public function verifyEmail(EmailVerificationRequest $request)
    {
        $request->fulfill();

        return redirect()->route('front')->with('success', 'Your email has been verified.');
    }

    /**
     * Verification process: Resend the email verification link
     *
     * @return \Illuminate\Http\Response
     */
    public function verifyResendEmail(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Verification link sent! Check your email to verify your account.');
    }

    /**
     * Reset process: Show the password reset form
     *
     * @return \Illuminate\Http\Response
     */
    public function resetRequestForm()
    {
        return view('account.resetRequest');
    }

    /**
     * Reset process: Show the password reset form
     *
     * @param  string  $token
     * @return \Illuminate\Http\Response
     */
    public function resetForm(Request $request, $token)
    {
        return view('account.resetForm', ['token' => $token]);
    }

    /**
     * Reset process: Send the password reset link
     *
     * @return \Illuminate\Http\Response
     */
    public function resetSendLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
                    ? back()->with('success', __($status))
                    : back()->with('error', __($status));
    }

    /**
     * Reset process: Reset the password based on the input form
     *
     * @return \Illuminate\Http\Response
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('success', __($status))
                    : back()->with('error', __($status));
    }
}
