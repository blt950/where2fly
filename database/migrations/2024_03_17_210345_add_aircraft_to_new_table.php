<?php

use App\Models\FlightAircraft;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        // Find and insert all aircraft to the new aircrafts table
        $aircrafts = FlightAircraft::select('aircraft_icao')->distinct()->get()->pluck('aircraft_icao');
        $upsertAircrafts = [];
        foreach ($aircrafts as $aircraft) {
            $upsertAircrafts[] = [
                'icao' => $aircraft,
            ];
        }

        DB::table('aircraft')->upsert($upsertAircrafts, ['icao']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('aircraft')->truncate();
    }
};
