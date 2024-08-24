<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Collection;

class MapHelper
{
    /**
     * Generate airport map data from airports
     *
     * @return string
     */
    public static function generateAirportMapDataFromAirports(Collection|array $airports)
    {
        $airportData = [];
        foreach ($airports as $airport) {
            $airportData[$airport->icao] = [
                'icao' => $airport->icao,
                'lat' => $airport->coordinates->latitude,
                'lon' => $airport->coordinates->longitude,
                'color' => $airport->color,
            ];
        }

        return json_encode($airportData);
    }

    /**
     * Get airports from user lists
     *
     * @return array
     */
    public static function getAirportsFromUserLists(Collection $userLists)
    {
        $airports = [];

        foreach ($userLists as $list) {
            foreach ($list->airports as $airport) {
                $airport->color = $list->color;
                $airports[] = $airport;
            }
        }

        return $airports;
    }
}
