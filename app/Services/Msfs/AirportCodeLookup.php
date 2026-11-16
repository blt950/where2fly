<?php

namespace App\Services\Msfs;

use App\Models\Airport;

class AirportCodeLookup
{
    /**
     * Which codes are airports, as [code => ICAO]. Like the list form, a code may be an ICAO or a
     * local code (C53 is KC53); a local code shared by several airports is left unmatched.
     *
     * @param  list<string>  $codes
     * @return array<string, string>
     */
    public function __invoke(array $codes): array
    {
        $rows = Airport::query()->toBase()->select('icao', 'local_code')
            ->whereIn('icao', $codes)->orWhereIn('local_code', $codes)->get();

        $byIcao = [];
        $byLocalCode = [];
        foreach ($rows as $row) {
            $icao = strtoupper($row->icao);
            $byIcao[$icao] = $icao;
            if ($row->local_code) {
                $byLocalCode[strtoupper($row->local_code)][$icao] = $icao;
            }
        }

        $known = [];
        foreach ($codes as $code) {
            if (isset($byIcao[$code])) {
                $known[$code] = $code;
            } elseif (count($byLocalCode[$code] ?? []) === 1) {
                $known[$code] = reset($byLocalCode[$code]);
            }
        }

        return $known;
    }
}
