<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AirportScore extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $guarded = [];

    public function airport(){
        return $this->belongsTo(Airport::class);
    }

    public function isWeatherScore(){
        return str_starts_with($this->reason, 'METAR_');
    }

    public function isVatsimScore(){
        return str_starts_with($this->reason, 'VATSIM_');
    }

    public static function getTopAirports($continent = null, $limit = 30){
        
        // Establish the return query
        $returnQuery = AirportScore::select('airport_id', \DB::raw("count(airport_scores.id) as id_count"))
        ->groupBy('airport_id')
        ->orderByDesc('id_count')
        ->join('airports', 'airport_scores.airport_id', '=', 'airports.id');

        // Filter on continent if supplied
        if($continent){

            // Include European and Russian-European airports
            if($continent == "EU"){
                $returnQuery = $returnQuery->where('airports.continent', $continent)
                ->whereNotIn('airports.iso_region', getRussianAsianRegions());
                              
            // Include Asian and Russian-Asian airports in a nested query for correct logic grouping
            } elseif($continent == "AS"){
                $returnQuery = $returnQuery->where(function($query) use ($continent){
                    $query->where('airports.continent', $continent)
                    ->orWhereIn('airports.iso_region', getRussianAsianRegions());
                });

            // Filter only on continent
            } else {
                $returnQuery = $returnQuery->where('airports.continent', $continent);
            }
        }

        // Filter airport type, relevant data and run the query
        $result = $returnQuery->whereIn('airports.type', ['large_airport','medium_airport','seaplane_base','small_airport'])
        ->with('airport', 'airport.metar', 'airport.runways', 'airport.scores')
        ->limit($limit)
        ->get();

        return $result;

    }
}
