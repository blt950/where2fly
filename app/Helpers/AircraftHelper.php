<?php

namespace App\Helpers;

class AircraftHelper
{
    /**
     * The canonical aircraft type registry: display name, example aircraft,
     * minimum runway length, cruise speed and climb/descend time allowance
     */
    public const TYPES = [
        'GA' => ['name' => 'Light GA', 'description' => 'C172/PA28/C182 etc.', 'min_runway_ft' => 100, 'cruise_kts' => 115, 'climb_descend_hours' => 0.13],
        'GAT' => ['name' => 'Turbo GA', 'description' => 'Bonanza/Baron/Caravan etc.', 'min_runway_ft' => 2000, 'cruise_kts' => 190, 'climb_descend_hours' => 0.20],
        'GTP' => ['name' => 'Heavy Turboprop', 'description' => 'TBM/PC-12/King Air etc.', 'min_runway_ft' => 2500, 'cruise_kts' => 280, 'climb_descend_hours' => 0.25],
        'JS' => ['name' => 'Regional Jet', 'description' => 'CRJ/E145/PC-24 etc.', 'min_runway_ft' => 4000, 'cruise_kts' => 340, 'climb_descend_hours' => 0.33],
        'JM' => ['name' => 'Narrow Body', 'description' => 'B737/A320/E190 etc.', 'min_runway_ft' => 5000, 'cruise_kts' => 460, 'climb_descend_hours' => 0.42],
        'JML' => ['name' => 'Mid Wide Body', 'description' => 'B757/B767 etc.', 'min_runway_ft' => 6000, 'cruise_kts' => 480, 'climb_descend_hours' => 0.47],
        'JL' => ['name' => 'Large Wide Body', 'description' => 'B777/B787/A350 etc.', 'min_runway_ft' => 7000, 'cruise_kts' => 510, 'climb_descend_hours' => 0.50],
        'JXL' => ['name' => 'Super Heavy', 'description' => 'B747/A380 etc.', 'min_runway_ft' => 8000, 'cruise_kts' => 520, 'climb_descend_hours' => 0.58],
    ];

    public static function codes(): array
    {
        return array_keys(self::TYPES);
    }

    public static function name(string $code): string
    {
        return self::TYPES[$code]['name'];
    }

    public static function isValidCode(mixed $code): bool
    {
        return is_string($code) && array_key_exists($code, self::TYPES);
    }

    public static function minimumRunwayFt(string $code): int
    {
        return self::TYPES[$code]['min_runway_ft'] ?? 0;
    }

    public static function cruiseKts(string $code): int
    {
        return self::TYPES[$code]['cruise_kts'] ?? 0;
    }

    public static function climbDescendHours(string $code): float
    {
        return self::TYPES[$code]['climb_descend_hours'] ?? 0;
    }
}
