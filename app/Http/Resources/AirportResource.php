<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AirportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'icao' => $this->icao,
            'iata' => $this->iata_code ?: null,
            'continent' => $this->continent,
            'country' => $this->iso_country,
            'region' => $this->iso_region,
            'metar' => app()->isProduction() ? $this->metar->metar : 'TEST-DATA ' . $this->metar->metar,
            'taf' => optional($this->taf)->raw_text,
            'longestRwyFt' => $this->longestRunway(),
            'scores' => $this->displayScores()->pluck('reason'),
        ];
    }
}
