<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

/**
 * The flash messages and the email-verification notice, decided once. shell/Alerts.jsx only
 * lays them out. Messages are plain text, rendered escaped.
 */
class AlertHelper
{
    private const VARIANTS = ['error' => 'danger', 'warning' => 'warning', 'success' => 'success'];

    public static function build(): array
    {
        $session = session();
        $user = Auth::user();

        $messages = collect(self::VARIANTS)
            ->map(fn (string $variant, string $key) => filled($text = $session->get($key))
                ? ['key' => $key, 'variant' => $variant, 'text' => (string) $text]
                : null)
            ->filter()
            ->values()
            ->all();

        // Not alongside a success message, which is usually the verification itself going through
        $verification = $user && ! $user->hasVerifiedEmail() && ! $session->has('success')
            ? ['email' => $user->email, 'action' => route('verification.send')]
            : null;

        return ['messages' => $messages, 'verification' => $verification];
    }
}
