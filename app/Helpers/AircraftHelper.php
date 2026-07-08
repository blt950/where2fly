<?php

namespace App\Helpers;

class AircraftHelper
{
    /**
     * The canonical list of aircraft type codes with their display name and
     * example aircraft, used by the search form, top list and validation
     */
    public const TYPES = [
        'GA' => ['name' => 'Light GA', 'description' => 'C172/PA28/C182 etc.'],
        'GAT' => ['name' => 'Turbo GA', 'description' => 'Bonanza/Baron/Caravan etc.'],
        'GTP' => ['name' => 'Heavy Turboprop', 'description' => 'TBM/PC-12/King Air etc.'],
        'JS' => ['name' => 'Regional Jet', 'description' => 'CRJ/E145/PC-24 etc.'],
        'JM' => ['name' => 'Narrow Body', 'description' => 'B737/A320/E190 etc.'],
        'JML' => ['name' => 'Mid Wide Body', 'description' => 'B757/B767 etc.'],
        'JL' => ['name' => 'Large Wide Body', 'description' => 'B777/B787/A350 etc.'],
        'JXL' => ['name' => 'Super Heavy', 'description' => 'B747/A380 etc.'],
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
}
