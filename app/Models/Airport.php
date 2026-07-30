<?php

namespace App\Models;

use App\Helpers\AircraftHelper;
use App\Helpers\CalculationHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Location\Coordinate;
use MatanYadaev\EloquentSpatial\Enums\Srid;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Airport extends Model
{
    use HasFactory;
    use HasSpatial;

    /** The canonical ground-to-air facility ordering for ATC display (dots, tooltips, stored station lists) */
    public const ATC_FACILITY_ORDER = ['DEL', 'GND', 'TWR', 'APP'];

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'coordinates' => Point::class,
        ];
    }

    public function metar()
    {
        return $this->hasOne(Metar::class);
    }

    public function taf()
    {
        return $this->hasOne(Taf::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function runways()
    {
        return $this->hasMany(Runway::class);
    }

    public function scores()
    {
        return $this->hasMany(AirportScore::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function controllers()
    {
        return $this->hasMany(Controller::class);
    }

    public function arrivalFlights()
    {
        return $this->hasMany(Flight::class, 'airport_arr_id');
    }

    public function departureFlights()
    {
        return $this->hasMany(Flight::class, 'airport_dep_id');
    }

    public function departureFlightsTo($toIcao, $seenThreshold = 3)
    {
        return $this->departureFlights()->where('arr_icao', $toIcao)->where('seen_counter', '>', $seenThreshold)->get()->groupBy('airline_icao');
    }

    public function sceneryDevelopers()
    {
        return $this->hasMany(SceneryDeveloper::class);
    }

    public function notableAirport()
    {
        return $this->hasOne(NotableAirport::class);
    }

    public function notableAirportTags()
    {
        return $this->hasMany(NotableAirportTag::class);
    }

    #[Scope]
    protected function publishedSceneries(Builder $query, $published, $filterSimulatorId = null): void
    {
        $query->whereHas('sceneryDevelopers', function ($query) use ($published, $filterSimulatorId) {
            $query->whereHas('sceneries', function ($query) use ($published, $filterSimulatorId) {
                $query->where('published', $published);
                if ($filterSimulatorId) {
                    $query->where('simulator_id', $filterSimulatorId);
                }
            });
        });
    }

    /**
     * The loaded scores applicable at the given ETA, plus whether a TAF period
     * covers it (drives the METAR fallback and the forecastSource indicator).
     *
     * @return array{0: Collection, 1: bool}
     */
    public function scoresAtEta(Carbon $eta, bool $metarOnlyWeather = false): array
    {
        $hasTafAtEta = (bool) $this->taf?->forecasts->contains(
            fn ($forecast) => $forecast->valid_from->lte($eta) && $forecast->valid_to->gte($eta)
        );

        return [
            $this->scores->filter(fn ($score) => $score->coversEtaAt($eta, $hasTafAtEta, $metarOnlyWeather))->values(),
            $hasTafAtEta,
        ];
    }

    /**
     * The loaded, ETA-windowed booking-sourced VATSIM_ATC scores, ordered by
     * start time — the tooltip and facility dots on the ATC icon render these.
     * A facility booked several times over the exact same window (e.g. two
     * positions both resolving to APP) renders as one line; the same facility
     * over a different window stays its own line.
     */
    public function atcBookingScores()
    {
        return $this->scores
            ->filter(fn ($score) => $score->reason === 'VATSIM_ATC' && $score->source === AirportScore::SOURCE_BOOKING)
            ->unique(fn ($score) => ($score->data['facility'] ?? $score->data['callsign'] ?? '') . '|' . $score->valid_from . '|' . $score->valid_to)
            ->sortBy('valid_from')
            ->values();
    }

    /**
     * The unique booked facility types (DEL/GND/TWR/APP) among those scores,
     * in ground-to-air order.
     */
    public function atcBookedFacilities()
    {
        return $this->sortFacilities(
            $this->atcBookingScores()->map(fn ($score) => $score->data['facility'] ?? null)
        );
    }

    /**
     * The stations online right now ({facility, logon_time} pairs), in
     * ground-to-air order. Read from the live VATSIM_ATC score when present,
     * otherwise from the logon-estimate rows still predicting presence at the ETA.
     */
    public function atcOnlineStations()
    {
        $liveAtc = $this->scores->first(fn ($score) => $score->reason === 'VATSIM_ATC' && $score->source === AirportScore::SOURCE_VATSIM);

        $stations = collect($liveAtc?->data['stations'] ?? []);
        if ($stations->isEmpty()) {
            $stations = $this->scores
                ->filter(fn ($score) => $score->reason === 'VATSIM_ATC' && $score->source === AirportScore::SOURCE_LOGON_ESTIMATE)
                ->map(fn ($score) => ['facility' => $score->data['facility'] ?? null, 'logon_time' => $score->data['logon_time'] ?? null])
                ->filter(fn ($station) => $station['logon_time'] !== null)
                ->unique('facility');
        }

        return $stations
            ->filter(fn ($station) => in_array($station['facility'] ?? null, self::ATC_FACILITY_ORDER))
            ->sortBy(fn ($station) => array_search($station['facility'], self::ATC_FACILITY_ORDER))
            ->values();
    }

    /**
     * The facility types online right now.
     */
    public function atcOnlineFacilities()
    {
        return $this->sortFacilities($this->atcOnlineStations()->pluck('facility'));
    }

    /**
     * Every facility type either online or booked — the ATC icon's colored dots.
     */
    public function atcFacilities()
    {
        return $this->sortFacilities($this->atcOnlineFacilities()->merge($this->atcBookedFacilities()));
    }

    private function sortFacilities(Collection $facilities): Collection
    {
        return $facilities
            ->filter(fn ($facility) => in_array($facility, self::ATC_FACILITY_ORDER))
            ->unique()
            ->sortBy(fn ($facility) => array_search($facility, self::ATC_FACILITY_ORDER))
            ->values();
    }

    /**
     * The loaded scores deduplicated to one row per reason for rendering.
     * Several sources can assert the same reason: current signals beat
     * forecasts (source order below), a certain row beats an uncertain
     * TEMPO/PROB one (matching how the ranking takes each reason's best
     * weight), and within a source the latest-starting row wins so the most
     * recent forecast period speaks.
     */
    public function displayScores()
    {
        $sourceOrder = array_flip([
            AirportScore::SOURCE_VATSIM,
            AirportScore::SOURCE_BOOKING,
            AirportScore::SOURCE_EVENT,
            AirportScore::SOURCE_LOGON_ESTIMATE,
            AirportScore::SOURCE_METAR,
            AirportScore::SOURCE_TAF,
        ]);

        return $this->scores
            ->sortBy([
                fn ($a, $b) => ($sourceOrder[$a->source] ?? 99) <=> ($sourceOrder[$b->source] ?? 99),
                fn ($a, $b) => $b->score <=> $a->score,
                fn ($a, $b) => $b->valid_from <=> $a->valid_from,
            ])
            ->unique('reason')
            ->values();
    }

    public function hasWeatherScore()
    {
        return $this->scores->contains(fn ($s) => $s->isWeatherScore());
    }

    public function weatherScore()
    {
        return $this->scores->filter(fn ($s) => $s->isWeatherScore())->count();
    }

    public function hasVatsimScore()
    {
        return $this->scores->contains(fn ($s) => $s->isVatsimScore());
    }

    public function vatsimScore()
    {
        return $this->scores->filter(fn ($s) => $s->isVatsimScore())->count();
    }

    public function longestRunway()
    {
        return $this->runways->where('closed', false)->max('length_ft') ?? 0;
    }

    public function hasVisualCondition()
    {
        return $this->metar->isVisualCondition();
    }

    /*
    ============================================================
        Search scopes and functions
    ============================================================
    */

    /**
     * Scope a query to only include airports that are considered open and have open runways
     */
    #[Scope]
    protected function airportOpen(Builder $query): void
    {
        $query->where('type', '!=', 'closed')->where('w2f_has_open_runway', true);
    }

    /**
     * Scope a query to only include airports that are not the departure airport
     */
    #[Scope]
    protected function notIcao(Builder $query, ?string $icao = null): void
    {
        if (isset($icao)) {
            $query->where('icao', '!=', $icao);
        }
    }

    /**
     * Scope a query to only include airports that are of the given size
     */
    #[Scope]
    protected function isAirportSize(Builder $query, ?array $destinationAirportSize = null): void
    {
        if (isset($destinationAirportSize)) {
            $query->whereIn('type', $destinationAirportSize);
        } else {
            $query->whereIn('type', ['small_airport', 'medium_airport', 'large_airport']);
        }
    }

    /**
     * Scope a query to only include airports in the given continent
     */
    #[Scope]
    protected function inContinent(Builder $query, array $destinations): void
    {
        if (isset($destinations['continents'])) {
            $continents = $destinations['continents'];

            if (in_array('EU', $continents) && in_array('AS', $continents)) {
                $query->whereIn('airports.continent', $continents);
            } else {
                $query->where(function ($query) use ($continents) {
                    foreach ($continents as $continent) {
                        $query->orWhere(function ($query) use ($continent) {
                            if ($continent == 'EU') {
                                $query->where('airports.continent', $continent)
                                    ->whereNotIn('airports.iso_region', getRussianAsianRegions());
                            } elseif ($continent == 'AS') {
                                $query->where('airports.continent', $continent)
                                    ->orWhereIn('airports.iso_region', getRussianAsianRegions());
                            } else {
                                $query->where('airports.continent', $continent);
                            }
                        });
                    }
                });
            }
        }
    }

    /**
     * Scope a query to exclude airports in the given continents
     */
    #[Scope]
    protected function notInContinent(Builder $query, array $destinations): void
    {
        if (isset($destinations['continents'])) {
            $continents = $destinations['continents'];

            if (in_array('EU', $continents) && in_array('AS', $continents)) {
                $query->whereNotIn('airports.continent', $continents);
            } else {
                $query->where(function ($query) use ($continents) {
                    foreach ($continents as $continent) {
                        $query->where(function ($query) use ($continent) {
                            if ($continent == 'EU') {
                                $query->where('airports.continent', '!=', $continent)
                                    ->orWhereIn('airports.iso_region', getRussianAsianRegions());
                            } elseif ($continent == 'AS') {
                                $query->where('airports.continent', '!=', $continent)
                                    ->whereNotIn('airports.iso_region', getRussianAsianRegions());
                            } else {
                                $query->where('airports.continent', '!=', $continent);
                            }
                        });
                    }
                });
            }
        }
    }

    /**
     * Scope a query to only include airports in the given country
     */
    #[Scope]
    protected function inCountry(Builder $query, array $destinations, ?string $country = null): void
    {

        // If filter is domestic, that should override all other country filters
        if (isset($destinations['countries']) && $destinations['countries'] == 'Domestic') {
            $query->where('iso_country', $country);

            return;
        }

        // Filter on countries
        if (isset($destinations['countries'])) {
            $query->whereIn('iso_country', $destinations['countries']);
        }
    }

    /**
     * Scope a query to only include airports not in the given country
     */
    #[Scope]
    protected function notInCountry(Builder $query, array $destinations, ?string $country = null): void
    {
        // If filter is domestic, that should override all other country filters
        if (isset($destinations['countries']) && $destinations['countries'] == 'Domestic') {
            $query->where('iso_country', '!=', $country);

            return;
        }

        // Filter on countries
        if (isset($destinations['countries'])) {
            $query->whereNotIn('iso_country', $destinations['countries']);
        }
    }

    /**
     * Scope a query to only include airports in the US state
     */
    #[Scope]
    protected function inState(Builder $query, array $destinations): void
    {
        if (isset($destinations['states'])) {
            $query->whereIn('iso_region', $destinations['states']);
        }
    }

    /**
     * Scope a query to only include airports not in the given US state
     */
    #[Scope]
    protected function notInState(Builder $query, array $destinations): void
    {
        if (isset($destinations['states'])) {
            $query->whereNotIn('iso_region', $destinations['states']);
        }
    }

    /**
     * Scope a query to only include airports within the given distance
     */
    #[Scope]
    protected function withinDistance(Builder $query, Airport $departureAirport, float $minDistance, float $maxDistance, string $departureIcao): void
    {
        if (isset($departureIcao)) {
            $this->applyDistanceBoundingBox($query, $departureAirport, $maxDistance);

            $query->whereDistanceSphere('coordinates', $departureAirport->coordinates, '<=', $maxDistance * 1852);
            if ($minDistance > 0) {
                $query->whereDistanceSphere('coordinates', $departureAirport->coordinates, '>=', $minDistance * 1852);
            }
        }
    }

    /**
     * Bounding-box pre-filter for withinDistance so the SPATIAL index can
     * prune candidates before the exact (but slow) distance checks run.
     * The box always contains the whole search circle — padded because
     * "straight" east-west lines on a sphere bulge toward the poles — and is
     * skipped whenever it can't be a simple lat/lon rectangle (huge radii,
     * circles wrapping a pole, or crossing ±85°/the date line).
     */
    private function applyDistanceBoundingBox(Builder $query, Airport $anchorAirport, float $maxDistanceNm): void
    {
        // Beyond this the box covers most of the planet and prunes nothing
        if ($maxDistanceNm <= 0 || $maxDistanceNm > 4000) {
            return;
        }

        $lat = $anchorAirport->coordinates->latitude;
        $lon = $anchorAirport->coordinates->longitude;

        // Radius as an angle at the Earth's center, with a 5% safety margin
        $radiusRad = ($maxDistanceNm * 1852 * 1.05) / 6371009.0;

        $sinRatio = sin($radiusRad) / cos(deg2rad($lat));
        if (abs($sinRatio) >= 1) {
            // The circle wraps a pole — no finite longitude bounds exist
            return;
        }

        $deltaLat = rad2deg($radiusRad);
        $deltaLon = rad2deg(asin($sinRatio));

        // Worst-case poleward bulge of the box's east-west edges
        $lonSpanRad = deg2rad($deltaLon) * 2;
        $edgeSag = rad2deg($lonSpanRad ** 2 / 8 * 0.5);

        $south = $lat - $deltaLat - $edgeSag;
        $north = $lat + $deltaLat + $edgeSag;
        $west = $lon - $deltaLon;
        $east = $lon + $deltaLon;

        if ($north > 85 || $south < -85 || $west < -180 || $east > 180) {
            return;
        }

        $box = new Polygon([
            new LineString([
                new Point($south, $west),
                new Point($north, $west),
                new Point($north, $east),
                new Point($south, $east),
                new Point($south, $west),
            ]),
        ], Srid::WGS84);

        $query->whereWithin('coordinates', $box);
    }

    /**
     * Scope a query to only include airports that are in the given direction
     */
    #[Scope]
    protected function withinBearing(Builder $query, Airport $departureAirport, ?string $direction, float $minDistance, float $maxDistance): void
    {

        // Ignore this scope if direction is not set
        if (! isset($direction)) {
            return;
        }

        $airportLat = $departureAirport->coordinates->latitude;
        $airportLon = $departureAirport->coordinates->longitude;

        // Two strategies: a polygon wedge for near distances, plain lat/lon
        // comparisons beyond 800nm where the polygon gets too skewed
        $airportCoordinate = new Coordinate($airportLat, $airportLon);
        $directions = [
            'N' => 0,
            'NE' => 45,
            'E' => 90,
            'SE' => 135,
            'S' => 180,
            'SW' => 225,
            'W' => 270,
            'NW' => 315,
        ];

        // Adjust the max allowed distance in polygon (800nm then converted to meters)
        $polygonDistance = ($maxDistance > 800 ? 800 : $maxDistance) * 1852;
        $highEnd = CalculationHelper::calculateSphericalDestination($airportCoordinate, $directions[$direction] + 45, $polygonDistance);
        $lowEnd = CalculationHelper::calculateSphericalDestination($airportCoordinate, $directions[$direction] - 45, $polygonDistance);

        $query->where(function ($q) use ($airportLat, $airportLon, $highEnd, $lowEnd, $minDistance, $maxDistance, $direction) {

            // Polygon wedge from the origin, bearing ±45 degrees
            if ($minDistance <= 800) {
                $polygon = new Polygon([
                    new LineString([
                        new Point($airportLat, $airportLon),
                        new Point($highEnd->getLat(), $highEnd->getLng()),
                        new Point($lowEnd->getLat(), $lowEnd->getLng()),
                        new Point($airportLat, $airportLon),
                    ]),
                ], Srid::WGS84);

                $q->whereWithin('coordinates', $polygon);
            }

            // Beyond the wedge: plain lat/lon comparisons per direction
            if ($maxDistance > 800) {

                switch ($direction) {
                    case 'N':
                        $q->orWhereRaw('ST_X(coordinates) > ?', [$highEnd->getLat()]);
                        break;
                    case 'NE':
                        $q->orWhereRaw('(ST_X(coordinates) > ? AND ST_Y(coordinates) > ?)', [$highEnd->getLat(), $lowEnd->getLng()]);
                        break;
                    case 'E':
                        $q->orWhereRaw('ST_Y(coordinates) > ?', [$lowEnd->getLng()]);
                        break;
                    case 'SE':
                        $q->orWhereRaw('(ST_X(coordinates) < ? AND ST_Y(coordinates) > ?)', [$lowEnd->getLat(), $highEnd->getLng()]);
                        break;
                    case 'S':
                        $q->orWhereRaw('ST_X(coordinates) < ?', [$lowEnd->getLat()]);
                        break;
                    case 'SW':
                        $q->orWhereRaw('(ST_X(coordinates) < ? AND ST_Y(coordinates) < ?)', [$highEnd->getLat(), $lowEnd->getLng()]);
                        break;
                    case 'W':
                        $q->orWhereRaw('ST_Y(coordinates) < ?', [$lowEnd->getLng()]);
                        break;
                    case 'NW':
                        $q->orWhereRaw('(ST_X(coordinates) > ? AND ST_Y(coordinates) < ?)', [$lowEnd->getLat(), $highEnd->getLng()]);
                        break;
                }
            }

        });
    }

    #[Scope]
    protected function filterRunwayLengths(Builder $query, int $rwyLengthMin, int $rwyLengthMax, string $codeletter): void
    {

        // Set minimum according to aircraft code unless it's already higher
        $codeMinimum = AircraftHelper::minimumRunwayFt($codeletter);
        if ($rwyLengthMin < $codeMinimum) {
            $rwyLengthMin = $codeMinimum;
        }

        // Get longest not closed runway
        $query->whereHas('runways', function ($query) use ($rwyLengthMin, $rwyLengthMax) {
            $query->where('closed', false)->where('length_ft', '>=', $rwyLengthMin)->where('length_ft', '<=', $rwyLengthMax);
        });

    }

    /**
     * Scope a query to only include airports that have runways with lights
     */
    #[Scope]
    protected function filterRunwayLights(Builder $query, ?int $destinationRunwayLights = null): void
    {
        if (isset($destinationRunwayLights) && $destinationRunwayLights !== 0) {

            if ($destinationRunwayLights == 1) {
                $query->whereHas('runways', function ($query) {
                    $query->where('lighted', true);
                });
            } elseif ($destinationRunwayLights == -1) {
                $query->whereDoesntHave('runways', function ($query) {
                    $query->where('lighted', true);
                });
            }

        }
    }

    /**
     * Scope a query to only include airports that are airbases
     */
    #[Scope]
    protected function filterAirbases(Builder $query, ?int $destinationAirbases = null): void
    {
        if (isset($destinationAirbases) && $destinationAirbases !== 0) {

            if ($destinationAirbases == 1) {
                $query->where('w2f_airforcebase', true);
            } elseif ($destinationAirbases == -1) {
                $query->where('w2f_airforcebase', false);
            }

        }
    }

    /**
     * Scope a query to only include airports that have scores. When an ETA is
     * given (a Carbon instant or a per-candidate SQL expression), only score
     * rows whose validity window applies at that ETA count.
     */
    #[Scope]
    protected function filterByScores(Builder $query, ?array $filterByScores = null, Carbon|string|null $eta = null, bool $metarOnlyWeather = false): void
    {
        if (isset($filterByScores) && ! empty($filterByScores)) {

            $query->where(function ($query) use ($filterByScores, $eta, $metarOnlyWeather) {
                foreach ($filterByScores as $score => $value) {
                    if ($value == 1) {
                        $query->whereHas('scores', function ($query) use ($score, $eta, $metarOnlyWeather) {
                            $query->where('reason', $score);
                            if ($eta) {
                                $query->coversEta($eta, $metarOnlyWeather);
                            }
                        });
                    } elseif ($value == -1) {
                        $query->whereDoesntHave('scores', function ($query) use ($score, $eta, $metarOnlyWeather) {
                            $query->where('reason', $score);
                            if ($eta) {
                                $query->coversEta($eta, $metarOnlyWeather);
                            }
                        });
                    }
                }
            });

        }
    }

    /**
     * Scope a query to only include airports that have routes and airlines
     */
    #[Scope]
    protected function filterRoutesAndAirlines(Builder $query, ?string $departureIcao = null, ?array $filterByAirlines = null, ?array $filterByAircrafts = null, ?int $destinationWithRoutesOnly = null, string $flightDirection = 'arrivalFlights'): void
    {
        if (isset($destinationWithRoutesOnly) && $destinationWithRoutesOnly !== 0) {

            if ($destinationWithRoutesOnly == 1) {
                $query->whereHas($flightDirection, function ($query) use ($departureIcao, $filterByAirlines, $flightDirection, $filterByAircrafts) {

                    if (isset($departureIcao)) {
                        if ($flightDirection == 'arrivalFlights') {
                            $query->where('dep_icao', $departureIcao);
                        } else {
                            $query->where('arr_icao', $departureIcao);
                        }
                    }

                    $query->where('flights.seen_counter', '>', 3);

                    if (isset($filterByAirlines)) {
                        $query->whereIn('airline_icao', $filterByAirlines);
                    }

                    if (isset($filterByAircrafts)) {
                        $query->whereHas('aircrafts', function ($query) use ($filterByAircrafts) {
                            $query->whereIn('aircraft.icao', $filterByAircrafts);
                        });
                    }
                });
            } elseif ($destinationWithRoutesOnly == -1) {
                $query->whereDoesntHave($flightDirection, function ($query) use ($departureIcao, $filterByAirlines, $flightDirection, $filterByAircrafts) {

                    if (isset($departureIcao)) {
                        if ($flightDirection == 'arrivalFlights') {
                            $query->where('dep_icao', $departureIcao);
                        } else {
                            $query->where('arr_icao', $departureIcao);
                        }
                    }

                    $query->where('flights.seen_counter', '>', 3);

                    if (isset($filterByAirlines)) {
                        $query->whereIn('airline_icao', $filterByAirlines);
                    }

                    if (isset($filterByAircrafts)) {
                        $query->whereHas('aircrafts', function ($query) use ($filterByAircrafts) {
                            $query->whereIn('aircraft.icao', $filterByAircrafts);
                        });
                    }
                });
            }

        } elseif (isset($filterByAirlines) || isset($filterByAircrafts)) {
            $query->whereHas($flightDirection, function ($query) use ($departureIcao, $filterByAirlines, $filterByAircrafts) {
                if (isset($departureIcao)) {

                    if ($filterByAirlines) {
                        $query->where('dep_icao', $departureIcao)->where('flights.seen_counter', '>', 3)->whereIn('airline_icao', $filterByAirlines);
                    }

                    if ($filterByAircrafts) {
                        $query->where('dep_icao', $departureIcao)->where('flights.seen_counter', '>', 3)->whereHas('aircrafts', function ($query) use ($filterByAircrafts) {
                            $query->whereIn('aircraft.icao', $filterByAircrafts);
                        });
                    }

                } else {

                    if ($filterByAirlines) {
                        $query->where('flights.seen_counter', '>', 3)->whereIn('airline_icao', $filterByAirlines);
                    }

                    if ($filterByAircrafts) {
                        $query->where('flights.seen_counter', '>', 3)->whereHas('aircrafts', function ($query) use ($filterByAircrafts) {
                            $query->whereIn('aircraft.icao', $filterByAircrafts);
                        });
                    }
                }
            });
        }
    }

    /**
     * Scope a query to only include airports that have the given scores
     */
    #[Scope]
    protected function returnOnlyWhitelistedIcao(Builder $query, ?array $whitelistedArrivals = null): void
    {
        if (isset($whitelistedArrivals)) {
            $query->whereIn('icao', $whitelistedArrivals);
        }
    }

    /**
     * Scope a query to sort airports by the summed weight of the given scores,
     * counting only rows valid at the ETA when one is given. The conditions
     * live in the join (not the where) so airports without scores still appear
     * with a count of zero. Each reason contributes its single best weight
     * (the MAX(CASE) pivot below) — several sources predicting the same reason
     * shouldn't outrank a single real signal, and a certain row asserting a
     * reason beats any uncertain TAF row asserting the same one.
     */
    #[Scope]
    protected function sortByScores(Builder $query, $filterByScores, Carbon|string|null $eta = null, bool $metarOnlyWeather = false)
    {
        if (isset($filterByScores) && ! empty($filterByScores)) {
            // Keep the historic airports.* select unless the caller already
            // narrowed the columns (the search pool queries select only the id
            // so the grouped temp table stays small and in memory)
            if (is_null($query->getQuery()->columns)) {
                $query->select('airports.*');
            }

            $reasons = array_values($filterByScores);
            $weightedSum = implode(' + ', array_fill(0, count($reasons), 'MAX(CASE WHEN airport_scores.reason = ? THEN airport_scores.score ELSE 0 END)'));

            return $query->leftJoin('airport_scores', function ($join) use ($reasons, $eta, $metarOnlyWeather) {
                $join->on('airports.id', '=', 'airport_scores.airport_id')
                    ->whereIn('airport_scores.reason', $reasons);
                if ($eta) {
                    AirportScore::applyCoversEta($join, $eta, $metarOnlyWeather);
                }
            })
                ->selectRaw("{$weightedSum} as score_count", $reasons)
                ->groupBy('airports.id')
                ->orderBy('score_count', 'desc');
        }
    }
}
