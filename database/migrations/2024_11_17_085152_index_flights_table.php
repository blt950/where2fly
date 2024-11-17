<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->index(['airport_arr_id', 'airline_icao', 'seen_counter']);
            $table->index(['airport_arr_id','seen_counter']);
        });

        Schema::table('flight_aircraft', function (Blueprint $table) {
            $table->index(['flight_id', 'aircraft_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->dropIndex(['airport_arr_id', 'airline_icao', 'seen_counter']);
            $table->dropIndex(['airport_arr_id','seen_counter']);
        });

        Schema::table('flight_aircraft', function (Blueprint $table) {
            $table->dropIndex(['flight_id', 'aircraft_id']);
        });
    }
};
