<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Collection;

class MapHelper
{
    public const NOTABLETAGS = [
        1 => ['icon' => 'fa-mountains', 'name' => 'Mountainous'],
        2 => ['icon' => 'fa-water', 'name' => 'Coastal'],
        3 => ['icon' => 'fa-route', 'name' => 'Hard Approach'],
        4 => ['icon' => 'fa-gauge-max', 'name' => 'High Altitude'],
        5 => ['icon' => 'fa-location-dot-slash', 'name' => 'Remote'],
        6 => ['icon' => 'fa-arrows-left-right-to-line', 'name' => 'Short Runway'],
        7 => ['icon' => 'fa-monument', 'name' => 'Legendary'],
    ];

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
                'id' => $airport->id,
                'icao' => $airport->icao,
                'lat' => $airport->coordinates->latitude,
                'lon' => $airport->coordinates->longitude,
                'color' => $airport->color,
                'type' => $airport->type,
            ];
        }

        return $airportData;
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

    /**
     * Generate notable airport category tags
     *
     * @return array|null
     */
    public static function getNotableCategories(Collection $ids)
    {
        $result = null;

        foreach (self::NOTABLETAGS as $tagId => $tag) {
            foreach ($ids as $id) {
                if ($tagId == $id) {
                    $result[] = $tag;
                }
            }
        }

        return $result;
    }
}
