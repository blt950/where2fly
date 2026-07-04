<?php

namespace App\Models;

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
     * The loaded scores applicable at the given ETA, plus whether the airport has
     * a scoreable TAF period covering it (drives the metar-fallback matching and
     * the forecastSource indicator).
     *
     * @return array{0: Collection, 1: bool}
     */
    public function scoresAtEta(Carbon $eta): array
    {
        $hasTafAtEta = (bool) $this->taf?->forecasts->contains(
            fn ($forecast) => $forecast->isScoreable() && $forecast->valid_from->lte($eta) && $forecast->valid_to->gte($eta)
        );

        return [
            $this->scores->filter(fn ($score) => $score->coversEtaAt($eta, $hasTafAtEta))->values(),
            $hasTafAtEta,
        ];
    }

    /**
     * The loaded, ETA-windowed booking-sourced VATSIM_ATC scores, ordered by
     * start time — the tooltip and facility dots on the ATC icon render these.
     */
    public function atcBookingScores()
    {
        return $this->scores
            ->filter(fn ($score) => $score->reason === 'VATSIM_ATC' && $score->source === AirportScore::SOURCE_BOOKING)
            ->sortBy('valid_from')
            ->values();
    }

    /**
     * The unique booked facility types (DEL/GND/TWR/APP) among those scores,
     * in ground-to-air order.
     */
    public function atcBookedFacilities()
    {
        $referenceOrder = ['DEL', 'GND', 'TWR', 'APP'];

        return $this->atcBookingScores()
            ->map(fn ($score) => $score->data['facility'] ?? null)
            ->filter()
            ->unique()
            ->sortBy(fn ($facility) => array_search($facility, $referenceOrder))
            ->values();
    }

    /**
     * The loaded scores deduplicated to one row per reason for rendering —
     * several sources can assert the same reason (e.g. a booking and an event
     * both predicting VATSIM_ATC). The row starting latest wins, so the most
     * recent forecast period speaks for overlapping windows.
     */
    public function displayScores()
    {
        return $this->scores->sortByDesc('valid_from')->unique('reason')->values();
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
            $query->whereDistanceSphere('coordinates', $departureAirport->coordinates, '<=', $maxDistance * 1852)->whereDistanceSphere('coordinates', $departureAirport->coordinates, '>=', $minDistance * 1852);
        }
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

        // We calculate bearing in two ways, depending on the distance.
        // First we calculate it within a polygon up to a certain limit
        // Second we calculate just X/Y coordinates if it's outside the limit
        // This is because the polygon gets very skewed after a certain distance

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

        // If the distance is less than 800nm, we can use a polygon
        $query->where(function ($q) use ($airportLat, $airportLon, $highEnd, $lowEnd, $minDistance, $maxDistance, $direction) {

            // >>> Step 1: Create a polygon from the origin, then the bearing + 45 degrees in each direction
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

            // >>> Step 2: Calculate the lat/long's for the max distance
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
        $codeMinimum = CalculationHelper::minimumRequiredRunwayLength($codeletter);
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
    protected function filterByScores(Builder $query, ?array $filterByScores = null, Carbon|string|null $eta = null): void
    {
        if (isset($filterByScores) && ! empty($filterByScores)) {

            $query->where(function ($query) use ($filterByScores, $eta) {
                foreach ($filterByScores as $score => $value) {
                    if ($value == 1) {
                        $query->whereHas('scores', function ($query) use ($score, $eta) {
                            $query->where('reason', $score);
                            if ($eta) {
                                $query->coversEta($eta);
                            }
                        });
                    } elseif ($value == -1) {
                        $query->whereDoesntHave('scores', function ($query) use ($score, $eta) {
                            $query->where('reason', $score);
                            if ($eta) {
                                $query->coversEta($eta);
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
     * Scope a query to sort airports by how many of the given scores they have,
     * counting only rows valid at the ETA when one is given. The reason and
     * window conditions live in the join clause, not the where clause, so an
     * airport with no applicable scores still appears — with a count of zero —
     * instead of dropping out of the results. Distinct reasons are counted:
     * several sources can predict the same VATSIM_ATC reason, which shouldn't
     * outrank an airport with a single real signal.
     */
    #[Scope]
    protected function sortByScores(Builder $query, $filterByScores, Carbon|string|null $eta = null)
    {
        if (isset($filterByScores) && ! empty($filterByScores)) {
            return $query->leftJoin('airport_scores', function ($join) use ($filterByScores, $eta) {
                $join->on('airports.id', '=', 'airport_scores.airport_id')
                    ->whereIn('airport_scores.reason', $filterByScores);
                if ($eta) {
                    AirportScore::applyCoversEta($join, $eta);
                }
            })
                ->selectRaw('airports.*, COUNT(DISTINCT airport_scores.reason) as score_count')
                ->groupBy('airports.id')
                ->orderBy('score_count', 'desc');
        }
    }
}
