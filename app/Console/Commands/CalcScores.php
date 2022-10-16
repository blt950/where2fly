<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Airport;
use App\Models\AirportScore;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CalcScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calc:scores';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate scores of airports';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        
        // Purge the table for a new calculation
        $processTime = microtime(true);
        $this->info("Starting calculations of aerodrome scores");

        // Clean old scores
        AirportScore::truncate();
        DB::table('airports')->update(['total_score' => null]);

        // Grab relevant aerodromes for calculations
        $airports = Airport::where('type', '!=', 'closed')->has('metar')->get();

        foreach($airports as $airport){

            // Skip airport with old metars
            if(Carbon::now()->gt(Carbon::parse($airport->metar->last_update)->addHour(1))){
                continue;
            }

            // Start scoring the airport
            $airportScore = 0;

            if($airport->metar->windAtAbove(15)){
                AirportScore::create(['airport_id' => $airport->id, 'reason' => 'METAR_WINDY', 'score' => 1]);
                $airportScore++;
            }

            if($airport->metar->windGusts()){
                AirportScore::create(['airport_id' => $airport->id, 'reason' => 'METAR_GUSTS', 'score' => 1]);
                $airportScore++;
            }

            if($airport->metar->ceilingAtAbove(300)){
                AirportScore::create(['airport_id' => $airport->id, 'reason' => 'METAR_CEILING', 'score' => 1]);
                $airportScore++;
            }

            if($airport->metar->foggy()){
                AirportScore::create(['airport_id' => $airport->id, 'reason' => 'METAR_FOGGY', 'score' => 1]);
                $airportScore++;
            }

            if($airport->metar->heavyRain()){
                AirportScore::create(['airport_id' => $airport->id, 'reason' => 'METAR_HEAVY_RAIN', 'score' => 1]);
                $airportScore++;
            }

            if($airport->metar->heavySnow()){
                AirportScore::create(['airport_id' => $airport->id, 'reason' => 'METAR_HEAVY_SNOW', 'score' => 1]);
                $airportScore++;
            }

            if($airport->metar->thunderstorm()){
                AirportScore::create(['airport_id' => $airport->id, 'reason' => 'METAR_THUNDERSTORM', 'score' => 1]);
                $airportScore++;
            }

            $activeRunwayComponents = ['headwind' => 0, 'crosswind' => 0];
            foreach($airport->runways->where('closed', false) as $runway){
                // Check RVR at runways
                if(
                    ( !empty($runway->le_ident) && $airport->metar->rvrAtBelow($runway->le_ident, 700) ) ||
                    ( !empty($runway->he_ident) && $airport->metar->rvrAtBelow($runway->he_ident, 700) )
                ){
                    AirportScore::create(['airport_id' => $airport->id, 'reason' => 'METAR_RVR', 'score' => 1]);
                    $airportScore++;
                }

                // Calculate headwind component on active runway
                if(!empty($airport->metar->wind_direction) && !empty($runway->le_heading) && !empty($runway->he_heading)){

                    $headwindComponentLe = abs($airport->metar->wind_speed * cos(($airport->metar->wind_direction - $runway->le_heading)/180*3.14));
                    $crosswindComponentLe = abs($airport->metar->wind_speed * sin(($airport->metar->wind_direction - $runway->le_heading)/180*pi()));

                    $headwindComponentHe = abs($airport->metar->wind_speed * cos(($airport->metar->wind_direction - $runway->le_heading)/180*3.14));
                    $crosswindComponentHe = abs($airport->metar->wind_speed * sin(($airport->metar->wind_direction - $runway->le_heading)/180*pi()));

                    if($activeRunwayComponents['headwind'] < $headwindComponentLe){
                        $activeRunwayComponents['headwind'] = $headwindComponentLe;
                        $activeRunwayComponents['crosswind'] = $crosswindComponentLe;
                    } else if($activeRunwayComponents['headwind'] < $headwindComponentHe){
                        $activeRunwayComponents['headwind'] = $headwindComponentHe;
                        $activeRunwayComponents['crosswind'] = $crosswindComponentHe;
                    }
                }
            }

            // Check if crosswind component is fun at active runway
            if($airport->metar->wind_speed >= 15 && $activeRunwayComponents['crosswind'] > 12){
                AirportScore::create(['airport_id' => $airport->id, 'reason' => 'METAR_CROSSWIND', 'score' => 1]);
                 $airportScore++;
            }

            $airport->total_score = $airportScore;
            $airport->save();

        }

        $this->info("Calculations of ".$airports->count()." aerodromes finished in ".round(microtime(true) - $processTime)." seconds");

    }
}
