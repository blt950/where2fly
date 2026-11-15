<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

/**
 * Every navigation link, defined once. The shell's header, bottom nav and footer (shell/Menu,
 * BottomNav, Footer) only lay this out.
 */
class NavigationHelper
{
    public static function build(): array
    {
        $user = Auth::user();

        $main = [
            self::link('search', 'Search', route('front'), 'fa-search', ['front*', 'search*'], shell: true),
            self::link('top', 'Top List', route('top'), 'fa-list', ['top*'], shell: true),
            self::link('scenery', 'Scenery', route('scenery'), 'fa-map', ['scenery', 'scenery.filtered'], shell: true),
        ];

        // Shared by AdminVariables / FeedbackVariables, which run before any view or Inertia response renders
        $feedback = [
            ...self::link('feedback', 'Feedback', route('feedback'), 'fa-message', ['feedback*'], shell: true),
            'unread' => (bool) View::shared('unreadFeedback'),
        ];

        $account = $user ? [
            'label' => $user->username,
            'notifications' => (int) View::shared('hasNotifications', 0),
            'items' => array_values(array_filter([
                self::link('settings', 'Settings', route('user.account'), null, ['user.account'], shell: true),
                self::link('lists', 'My Lists', route('list.index'), null, ['list.*'], shell: true),
                $user->can('showAdmin', $user::class) ? self::link('admin', 'Admin', route('admin'), null, ['admin']) : null,
                self::link('logout', 'Logout', route('user.logout'), null, []),
            ])),
        ] : null;

        if ($account) {
            $account['active'] = collect($account['items'])->contains('active', true);
        }

        $login = $user ? null : self::link('login', 'Login', route('login'), 'fa-lock', ['login', 'register', 'account.recovery', 'password.reset'], shell: true);

        $community = [
            self::external('discord', 'Discord', 'https://discord.gg/UkFg9Yy4gP', 'fab fa-discord'),
            self::external('github', 'GitHub', 'https://github.com/blt950/where2fly', 'fab fa-github'),
            self::external('donate', 'Buy me a coffee', 'https://ko-fi.com/X7X21V3MEX', null),
        ];

        $info = [
            self::link('changelog', 'Changelog', route('changelog'), null, ['changelog'], shell: true),
            self::link('privacy', 'Privacy Policy', route('privacy'), null, ['privacy'], shell: true),
            self::link('api', 'API', route('api'), null, ['api'], shell: true),
        ];

        return [
            'main' => $main,
            'feedback' => $feedback,
            'account' => $account,
            'login' => $login,
            'community' => $community,
            'info' => $info,
            // Everything behind the bottom nav's "More": it lights up when one of these is the page
            'moreActive' => $feedback['active'] || ($account['active'] ?? false) || ($login['active'] ?? false)
                || collect($info)->contains('active', true),
            'version' => config('app.version'),
        ];
    }

    /**
     * `shell`: the target is an Inertia page, so the shell links to it client-side and keeps the map
     */
    private static function link(string $key, string $label, string $href, ?string $icon, array $active, bool $shell = false): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'href' => $href,
            'icon' => $icon,
            'active' => $active !== [] && Route::is(...$active),
            'shell' => $shell,
            'external' => false,
        ];
    }

    private static function external(string $key, string $label, string $href, ?string $icon): array
    {
        return ['key' => $key, 'label' => $label, 'href' => $href, 'icon' => $icon, 'active' => false, 'shell' => false, 'external' => true];
    }
}
