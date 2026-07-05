<?php

namespace App\Helpers;

class AirportCallsignHelper
{
    public static function returnAustralianAirport($string)
    {
        $australianCallsigns = ['AD' => 'YPAD', 'BN' => 'YBBN', 'CS' => 'YBCS', 'CB' => 'YSCB', 'DN' => 'YPDN', 'EN' => 'YMEN', 'CG' => 'YBCG', 'ML' => 'YMML', 'PH' => 'YPPH', 'SY' => 'YSSY', 'TL' => 'YBTL', 'WLM' => 'YWLM', 'AMB' => 'YAMB', 'CIN' => 'YCIN', 'AF' => 'YBAF', 'AV' => 'YMAV', 'BK' => 'YSBK', 'CN' => 'YSCN', 'HB' => 'YMHB', 'JT' => 'YPJT', 'LT' => 'YMLT', 'MK' => 'YBMK', 'MB' => 'YMMB', 'PF' => 'YPPF', 'RK' => 'YBRK', 'AY' => 'YMAY', 'AS' => 'YBAS', 'BRM' => 'YBRM', 'CFS' => 'YCFS', 'HM' => 'YBHM', 'KA' => 'YPKA', 'SU' => 'YBSU', 'TW' => 'YSTW', 'WR' => 'YPWR'];

        return $australianCallsigns[$string] ?? false;
    }

    public static function returnAmericanIcao($string)
    {
        return 'K' . $string;
    }

    /**
     * The facility type of a position callsign — its part after the last
     * underscore (EGLL_TWR → TWR). Null when there's no underscore to split on.
     */
    public static function facility(string $callsign): ?string
    {
        $suffix = strrchr($callsign, '_');

        return $suffix === false ? null : substr($suffix, 1);
    }

    /**
     * Resolve a position callsign (e.g. EGLL_TWR, JFK_GND) to an ICAO code,
     * using the same prefix algorithm fetch:vatsim applies to online controllers.
     * Returns null for positions that don't represent a single airport.
     */
    public static function resolveIcao(string $callsign): ?string
    {
        // Enroute, oceanic and ATIS positions don't map to a single airport
        if (preg_match('/_(CTR|FSS|ATIS)$/', $callsign)) {
            return null;
        }

        // Fetch callsign prefix
        if (! preg_match('/^([A-Z]*)_/', $callsign, $matches) || $matches[1] === '') {
            return null;
        }

        // For callsigns with 3 letters or less
        if (strlen($matches[1]) <= 3) {
            return self::returnAustralianAirport($matches[1]) ?: self::returnAmericanIcao($matches[1]);
        }

        return $matches[1];
    }
}
