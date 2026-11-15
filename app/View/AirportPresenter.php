<?php

namespace App\View;

use App\Models\Airport;
use App\Models\AirportScore;

/**
 * The airport shapes the pages share: the Top List and the search results show an airport, and its
 * score icons, the same way.
 */
class AirportPresenter
{
    public static function summary(Airport $airport): array
    {
        return [
            'icao' => $airport->icao,
            'name' => $airport->name,
            'isoCountry' => $airport->iso_country,
            'countryName' => getCountryName($airport->iso_country),
        ];
    }

    /** Bookings are labelled with the side of the flight they cover, where there is one */
    public static function scoreIcons(Airport $airport, string $bookingsLabel = 'Bookings', array $highlightReasons = []): array
    {
        return $airport->displayScores()
            ->map(fn (AirportScore $score) => [
                ...(new ScoreIcon($score, $airport, bookingsLabel: $bookingsLabel))->toArray(),
                'highlighted' => in_array($score->reason, $highlightReasons),
            ])
            ->values()
            ->all();
    }
}
