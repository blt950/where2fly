<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        DB::table('flights')->select('id', 'aircraft_icao', 'last_seen_at', 'first_seen_at')
        ->orderBy('id')
        ->chunk(1000, function ($flights) {
            $insertAircraftData = [];
            foreach ($flights as $flight) {
                $insertAircraftData[] = [
                    'flight_id' => $flight->id,
                    'aircraft_icao' => $flight->aircraft_icao,
                    'last_seen_at' => $flight->last_seen_at,
                    'first_seen_at' => $flight->first_seen_at,
                ];
            }
            DB::table('flight_aircraft')->insert($insertAircraftData);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Ignore
    }
};
