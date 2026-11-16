<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

/**
 * The hint over the map, if any, decided once; shell/MapHint.jsx only lays it out, and a page
 * can still opt out of showing it (search results, Top List).
 */
class MapHintHelper
{
    public static function current(): ?array
    {
        $user = Auth::user();

        if (! $user) {
            return ['icon' => 'fa-lightbulb-on', 'text' => 'Create an account to fill the map with your own scenery list.'];
        }

        if ($user->lists()->doesntExist()) {
            return ['icon' => 'fa-lightbulb-on', 'text' => 'Create a scenery list to fill the map with your own data.'];
        }

        return null;
    }
}
