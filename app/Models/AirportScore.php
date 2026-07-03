<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AirportScore extends Model
{
    use HasFactory;

    public const SOURCE_METAR = 'metar';

    public const SOURCE_TAF = 'taf';

    public const SOURCE_VATSIM = 'vatsim';

    public const SOURCE_EVENT = 'event';

    public const SOURCE_BOOKING = 'booking';

    public const SOURCE_LOGON_ESTIMATE = 'logon_estimate';

    /** Sources whose window must contain the ETA exactly */
    public const EXACT_MATCH_SOURCES = [self::SOURCE_METAR, self::SOURCE_TAF, self::SOURCE_VATSIM, self::SOURCE_BOOKING];

    /** Inexact sources, matched with a ±2h interval overlap against the ETA */
    public const OVERLAP_MATCH_SOURCES = [self::SOURCE_EVENT, self::SOURCE_LOGON_ESTIMATE];

    /** Hours of query-time tolerance applied to the inexact sources */
    public const OVERLAP_MATCH_HOURS = 2;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'valid_from' => 'datetime',
            'valid_to' => 'datetime',
        ];
    }

    public function airport()
    {
        return $this->belongsTo(Airport::class);
    }

    public function isWeatherScore()
    {
        return str_starts_with($this->reason, 'METAR_');
    }

    public function isVatsimScore()
    {
        return str_starts_with($this->reason, 'VATSIM_');
    }

    public static function getTopAirports($continent = null, $whitelist = null, $limit = 30, $exclude = null)
    {

        // Establish the return query
        $returnQuery = AirportScore::select('airport_id', DB::raw('count(airport_scores.id) as id_count'))
            ->groupBy('airport_id')
            ->orderByDesc('id_count')
            ->join('airports', 'airport_scores.airport_id', '=', 'airports.id');

        // Filter out VATSIM scores if requested
        if ($exclude) {
            if ($exclude == 'vatsim') {
                $returnQuery = $returnQuery->where('airport_scores.reason', 'NOT LIKE', 'VATSIM_%');
            }
        }

        // Filter on continent if supplied
        if ($continent) {

            // Include European and Russian-European airports
            if ($continent == 'EU') {
                $returnQuery = $returnQuery->where('airports.continent', $continent)
                    ->whereNotIn('airports.iso_region', getRussianAsianRegions());

                // Include Asian and Russian-Asian airports in a nested query for correct logic grouping
            } elseif ($continent == 'AS') {
                $returnQuery = $returnQuery->where(function ($query) use ($continent) {
                    $query->where('airports.continent', $continent)
                        ->orWhereIn('airports.iso_region', getRussianAsianRegions());
                });

                // Filter only on continent
            } else {
                $returnQuery = $returnQuery->where('airports.continent', $continent);
            }
        }

        if ($whitelist && is_array($whitelist) && count($whitelist) > 0) {
            $returnQuery = $returnQuery->whereIn('airports.icao', $whitelist);
        }

        // Filter airport type, relevant data and run the query
        $result = $returnQuery->whereIn('airports.type', ['large_airport', 'medium_airport', 'seaplane_base', 'small_airport'])
            ->with('airport', 'airport.metar', 'airport.runways', 'airport.scores')
            ->limit($limit)
            ->get();

        return $result;

    }
}
