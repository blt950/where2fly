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
        // Create the new column
        Schema::table('flight_aircraft', function (Blueprint $table) {
            $table->unsignedBigInteger('aircraft_id')->nullable()->after('aircraft_icao');
            $table->foreign('aircraft_id')->references('id')->on('aircraft');
        });

        // Match the aircraft_icao to the id in the new table and set aircraft_id to this
        DB::statement('UPDATE flight_aircraft, aircraft SET flight_aircraft.aircraft_id = aircraft.id WHERE flight_aircraft.aircraft_icao = aircraft.icao');

        // Drop the old column
        Schema::table('flight_aircraft', function (Blueprint $table) {
            $table->unique(['flight_id', 'aircraft_id']);
            $table->dropUnique(['flight_id', 'aircraft_icao']);
            $table->dropColumn('aircraft_icao');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Create the new column
        Schema::table('flight_aircraft', function (Blueprint $table) {
            $table->string('aircraft_icao', 4)->nullable()->after('flight_id');
        });

        // Match the aircraft_id to the icao in the new table and set aircraft_icao to this
        DB::statement('UPDATE flight_aircraft, aircraft SET flight_aircraft.aircraft_icao = aircraft.icao WHERE flight_aircraft.aircraft_id = aircraft.id');

        // Drop the new column
        Schema::table('flight_aircraft', function (Blueprint $table) {
            $table->unique(['flight_id', 'aircraft_icao']);
            $table->dropUnique(['flight_id', 'aircraft_id']);
            $table->dropForeign(['aircraft_id']);
            $table->dropColumn('aircraft_id');
        });
    }
};
