<?php

namespace App\Models;

use App\Helpers\CalculationHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    protected $casts = [
        'coordinates' => Point::class,
    ];

    public function metar()
    {
        return $this->hasOne(Metar::class);
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

    public static function whereHasPublishedSceneries($published, $filterSimulatorId = null)
    {
        return Airport::whereHas('sceneryDevelopers', function ($query) use ($published, $filterSimulatorId) {
            $query->whereHas('sceneries', function ($query) use ($published, $filterSimulatorId) {
                $query->where('published', $published);
                if ($filterSimulatorId) {
                    $query->where('simulator_id', $filterSimulatorId);
                }
            });
        });
    }

    public function hasWeatherScore()
    {
        foreach ($this->scores as $s) {
            if ($s->isWeatherScore()) {
                return true;
            }
        }

        return false;
    }

    public function weatherScore()
    {
        $score = 0;
        foreach ($this->scores as $s) {
            if ($s->isWeatherScore()) {
                $score++;
            }
        }

        return $score;
    }

    public function hasVatsimScore()
    {
        foreach ($this->scores as $s) {
            if ($s->isVatsimScore()) {
                return true;
            }
        }

        return false;
    }

    public function vatsimScore()
    {
        $score = 0;
        foreach ($this->scores as $s) {
            if ($s->isVatsimScore()) {
                $score++;
            }
        }

        return $score;
    }

    public function longestRunway()
    {
        $length = 0;
        foreach ($this->runways as $rwy) {
            if ($rwy->closed == false && $rwy->length_ft > $length) {
                $length = $rwy->length_ft;
            }
        }

        return $length;
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
    public function scopeAirportOpen(Builder $query)
    {
        $query->where('type', '!=', 'closed')->whereHas('runways', function ($query) {
            $query->where('closed', false);
        });
    }

    /**
     * Scope a query to only include airports that are not the departure airport
     */
    public function scopeNotIcao(Builder $query, ?string $icao = null)
    {
        if (isset($icao)) {
            $query->where('icao', '!=', $icao);
        }
    }

    /**
     * Scope a query to only include airports that are of the given size
     */
    public function scopeIsAirportSize(Builder $query, ?array $destinationAirportSize = null)
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
    public function scopeInContinent(Builder $query, array $destinations)
    {
        if (isset($destinations['continents'])) {
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
    }

    /**
     * Scope a query to only include airports in the given country
     */
    public function scopeInCountry(Builder $query, array $destinations, ?string $country = null)
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
     * Scope a query to only include airports in the US state
     */
    public function scopeInState(Builder $query, array $destinations)
    {
        if (isset($destinations['states'])) {
            $query->whereIn('iso_region', $destinations['states']);
        }
    }

    /**
     * Scope a query to only include airports within the given distance
     */
    public function scopeWithinDistance(Builder $query, Airport $departureAirport, float $minDistance, float $maxDistance, string $departureIcao)
    {
        if (isset($departureIcao)) {
            $query->whereDistanceSphere('coordinates', $departureAirport->coordinates, '<=', $maxDistance * 1852)->whereDistanceSphere('coordinates', $departureAirport->coordinates, '>=', $minDistance * 1852);
        }
    }

    /**
     * Scope a query to only include airports that are in the given direction
     */
    public function scopeWithinBearing(Builder $query, Airport $departureAirport, ?string $direction, float $minDistance, float $maxDistance)
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

    public function scopeFilterRunwayLengths(Builder $query, int $rwyLengthMin, int $rwyLengthMax, string $codeletter)
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
    public function scopeFilterRunwayLights(Builder $query, ?int $destinationRunwayLights = null)
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
    public function scopeFilterAirbases(Builder $query, ?int $destinationAirbases = null)
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
     * Scope a query to only include airports that have scores
     */
    public function scopeFilterByScores(Builder $query, ?array $filterByScores = null)
    {
        if (isset($filterByScores) && ! empty($filterByScores)) {

            $query->where(function ($query) use ($filterByScores) {
                foreach ($filterByScores as $score => $value) {
                    if ($value == 1) {
                        $query->whereHas('scores', function ($query) use ($score) {
                            $query->where('reason', $score);
                        });
                    } elseif ($value == -1) {
                        $query->whereDoesntHave('scores', function ($query) use ($score) {
                            $query->where('reason', $score);
                        });
                    }
                }
            });

        }
    }

    /**
     * Scope a query to only include airports that have routes and airlines
     */
    public function scopeFilterRoutesAndAirlines(Builder $query, ?string $departureIcao = null, ?array $filterByAirlines = null, ?array $filterByAircrafts = null, ?int $destinationWithRoutesOnly = null, string $flightDirection = 'arrivalFlights')
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
    public function scopeReturnOnlyWhitelistedIcao(Builder $query, ?array $whitelistedArrivals = null)
    {
        if (isset($whitelistedArrivals)) {
            $query->whereIn('icao', $whitelistedArrivals);
        }
    }

    /**
     * Scope a query to only include airports that have the given scores
     */
    public function scopeSortByScores(Builder $query, $filterByScores)
    {
        if (isset($filterByScores) && ! empty($filterByScores)) {
            return $query->leftJoin('airport_scores', 'airports.id', '=', 'airport_scores.airport_id')
                ->selectRaw('airports.*, COUNT(airport_scores.id) as score_count')
                ->where(function ($query) use ($filterByScores) {
                    $query->whereIn('airport_scores.reason', $filterByScores)
                        ->orWhereNull('airport_scores.reason');
                })
                ->groupBy('airports.id')
                ->orderBy('score_count', 'desc');
        }
    }

    /**
     * Shuffle results around. Randomize the order of airports.
     */
    public function scopeShuffleAndSort(Builder $query)
    {
        $query->orderByRaw('RAND()');
    }
}
