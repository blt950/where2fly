<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
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
    public const EXACT_MATCH_SOURCES = [self::SOURCE_METAR, self::SOURCE_TAF, self::SOURCE_VATSIM, self::SOURCE_LOGON_ESTIMATE];

    /** Scheduled-presence sources, matched with a ±1h interval overlap against the ETA */
    public const OVERLAP_MATCH_SOURCES = [self::SOURCE_BOOKING, self::SOURCE_EVENT];

    /** Hours of query-time tolerance applied to the inexact sources */
    public const OVERLAP_MATCH_HOURS = 1;

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

    /**
     * Scope score rows to those applicable at the given ETA. $eta is either a
     * Carbon instant or a raw SQL expression (for per-candidate ETAs computed
     * in the query itself).
     *
     * With $metarOnlyWeather (departure candidates — the pilot leaves soon),
     * TAF rows never match and the current METAR always does: the latest
     * observation is the weather truth there, not a forecast.
     */
    #[Scope]
    protected function coversEta(Builder $query, Carbon|string $eta, bool $metarOnlyWeather = false): void
    {
        self::applyCoversEta($query, $eta, $metarOnlyWeather);
    }

    /**
     * PHP-side twin of the coversEta scope, for filtering already-loaded score
     * collections per candidate. The caller supplies whether the airport has a
     * TAF period covering the ETA (the metar-fallback input), since that spans
     * the whole airport, not this row.
     */
    public function coversEtaAt(Carbon $eta, bool $airportHasTafAtEta, bool $metarOnlyWeather = false): bool
    {
        if (in_array($this->source, self::OVERLAP_MATCH_SOURCES)) {
            return $this->valid_from->lte($eta->copy()->addHours(self::OVERLAP_MATCH_HOURS))
                && $this->valid_to->gte($eta->copy()->subHours(self::OVERLAP_MATCH_HOURS));
        }

        if ($this->source === self::SOURCE_METAR) {
            return $metarOnlyWeather
                || ! $airportHasTafAtEta
                || ($this->valid_from->lte($eta) && $this->valid_to->gte($eta));
        }

        if ($this->source === self::SOURCE_TAF && $metarOnlyWeather) {
            return false;
        }

        return $this->valid_from->lte($eta) && $this->valid_to->gte($eta);
    }

    /**
     * Same conditions as the coversEta scope, but applicable to any query
     * builder that has airport_scores in scope (e.g. a join on airports).
     *
     * Cost warning: when $eta is a per-candidate SQL expression
     * (forecastEtaSql's ST_DISTANCE_SPHERE arithmetic), it is inlined 3-4x
     * here and re-evaluated per probed row — and search already applies this
     * once per filtered reason plus in the sortByScores join. MySQL cannot
     * reference a select alias from WHERE/JOIN, so it can't be computed once
     * per row; don't add further coversEta call sites inside per-row
     * subqueries.
     */
    public static function applyCoversEta($query, Carbon|string $eta, bool $metarOnlyWeather = false): void
    {
        [$etaSql, $bindings] = $eta instanceof Carbon ? ['?', [$eta->toDateTimeString()]] : [$eta, []];

        // The ETA sits on the left of each BETWEEN so a per-candidate ETA
        // expression (forecastEtaSql's ST_DISTANCE_SPHERE arithmetic) is
        // evaluated once per comparison, not once per bound; the ±1h overlap
        // tolerance is moved onto the window side for the same reason.
        $query->where(function ($query) use ($etaSql, $bindings, $metarOnlyWeather) {
            // Exact containment for sources with a precise window
            $query->where(function ($query) use ($etaSql, $bindings, $metarOnlyWeather) {
                $exactSources = $metarOnlyWeather
                    ? array_diff(self::EXACT_MATCH_SOURCES, [self::SOURCE_METAR, self::SOURCE_TAF])
                    : self::EXACT_MATCH_SOURCES;

                $query->whereIn('airport_scores.source', $exactSources)
                    ->whereRaw("{$etaSql} BETWEEN airport_scores.valid_from AND airport_scores.valid_to", $bindings);
            });

            // Interval overlap with a tolerance for the scheduled-presence signals
            $query->orWhere(function ($query) use ($etaSql, $bindings) {
                $overlapHours = self::OVERLAP_MATCH_HOURS;

                $query->whereIn('airport_scores.source', self::OVERLAP_MATCH_SOURCES)
                    ->whereRaw("{$etaSql} BETWEEN DATE_SUB(airport_scores.valid_from, INTERVAL {$overlapHours} HOUR) AND DATE_ADD(airport_scores.valid_to, INTERVAL {$overlapHours} HOUR)", $bindings);
            });

            if ($metarOnlyWeather) {
                // Departure candidates: the current METAR is always the weather truth
                $query->orWhere('airport_scores.source', self::SOURCE_METAR);

                return;
            }

            // The current METAR is the fallback when no TAF period covers the ETA
            $query->orWhere(function ($query) use ($etaSql, $bindings) {
                $query->where('airport_scores.source', self::SOURCE_METAR)
                    ->whereNotExists(function ($query) use ($etaSql, $bindings) {
                        $query->from('taf_forecasts')
                            ->join('tafs', 'tafs.id', '=', 'taf_forecasts.taf_id')
                            ->whereColumn('tafs.airport_id', 'airport_scores.airport_id')
                            ->whereRaw("{$etaSql} BETWEEN taf_forecasts.valid_from AND taf_forecasts.valid_to", $bindings);
                    });
            });
        });
    }

    /**
     * A readable tooltip line built from the structured data payload,
     * shaped by which source generated the row.
     */
    public function tooltipText(): ?string
    {
        if (! $this->data) {
            return null;
        }

        return match ($this->source) {
            // Live rows carry either the aggregated station list (VATSIM_ATC)
            // or a movement count (VATSIM_POPULAR)
            self::SOURCE_VATSIM => match (true) {
                isset($this->data['stations']) => collect($this->data['stations'])->map(fn ($station) => $station['facility'] ?? $station)->join(', '),
                isset($this->data['movements']) => $this->data['movements'] . ' aircraft in vicinity',
                default => null,
            },

            self::SOURCE_EVENT => $this->data['event'] . ' ' . $this->windowText(),

            // A controller online right now — we know when they logged on, not when they'll leave
            self::SOURCE_LOGON_ESTIMATE => self::onlineForText($this->data['facility'] ?? $this->data['position'], $this->data['logon_time']),

            self::SOURCE_BOOKING => ($this->data['facility'] ?? $this->data['callsign']) . ' ' . $this->windowText(),

            default => null,
        };
    }

    private function windowText(): string
    {
        return $this->valid_from->format('H:i\z') . ' - ' . $this->valid_to->format('H:i\z');
    }

    /**
     * The single "TWR online for 1h 20m" formatter — every online-station line
     * renders through here.
     */
    public static function onlineForText(string $facility, Carbon|string $logonTime): string
    {
        return $facility . ' online for ' . self::loggedOnAgo($logonTime);
    }

    /**
     * How long ago a controller logged on, in hours and/or minutes
     */
    public static function loggedOnAgo(Carbon|string $logonTime): string
    {
        return Carbon::parse($logonTime)->diffForHumans(['parts' => 2, 'short' => true, 'minimumUnit' => 'minute', 'syntax' => Carbon::DIFF_ABSOLUTE]);
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
        // Don't cache whitelists
        if ($whitelist) {
            return self::computeTopAirports($continent, $whitelist, $limit, $exclude);
        }

        $cacheKey = 'top-airports:' . ($continent ?? 'all') . ':' . ($exclude ?? 'none') . ':' . $limit;

        // The payload is base64-wrapped because the loaded airports embed raw
        // GEOMETRY binary (coordinates), which the database cache store cannot
        // put in its text value column
        if ($cached = Cache::get($cacheKey)) {
            return unserialize(base64_decode($cached));
        }

        // Compute and cache the result
        $result = self::computeTopAirports($continent, $whitelist, $limit, $exclude);
        Cache::put($cacheKey, base64_encode(serialize($result)), 300);

        return $result;
    }

    private static function computeTopAirports($continent, $whitelist, $limit, $exclude)
    {

        // Establish the return query — counting distinct reasons
        $returnQuery = AirportScore::select('airport_id', DB::raw('count(distinct airport_scores.reason) as id_count'))
            ->coversEta(now())
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

        // Filter airport type, relevant data and run the query — the loaded scores
        // are windowed the same way as the count, so the view renders what was ranked
        $result = $returnQuery->whereIn('airports.type', ['large_airport', 'medium_airport', 'seaplane_base', 'small_airport'])
            ->with(['airport', 'airport.metar', 'airport.runways', 'airport.scores' => fn ($query) => $query->coversEta(now())])
            ->limit($limit)
            ->get();

        return $result;

    }
}
