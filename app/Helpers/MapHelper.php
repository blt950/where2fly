<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\UserList;

class MapHelper
{
    public static function generateAirportMapDataFromUserLists(User $user)
    {
        $airportData = [];
        $userLists = UserList::where('user_id', $user->id)->with('airports')->get();

        foreach ($userLists as $list) {
            foreach ($list->airports as $airport) {
                $airportData[$airport->icao] = [
                    'icao' => $airport->icao,
                    'lat' => $airport->coordinates->latitude,
                    'lon' => $airport->coordinates->longitude,
                    'color' => $list->color,
                ];
            }
        }

        return json_encode($airportData);
    }

    public static function generateAirportMapData($airports)
    {
        $airportData = [];
        foreach ($airports as $airport) {
            $airportData[$airport->icao] = [
                'icao' => $airport->icao,
                'lat' => $airport->coordinates->latitude,
                'lon' => $airport->coordinates->longitude,
            ];
        }

        return json_encode($airportData);
    }
}
