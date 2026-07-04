<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SuggestedAirportResource extends AirportResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'airtime' => $this->airtime,
            'distanceNm' => $this->distance,
            'isAirforcebase' => $this->w2f_airforcebase,
            'hasAirlineService' => $this->w2f_scheduled_service,
            // 'taf' when the scores are backed by a TAF period covering the ETA,
            // 'metar_fallback' when they're the current METAR presented unconfirmed,
            // 'metar' for departure suggestions (evaluated at now, not a forecast)
            'forecastSource' => $this->forecast_source,
        ]);
    }
}
