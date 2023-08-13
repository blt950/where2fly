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
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->string('airline_icao', 3);
            $table->string('airline_iata', 3);
            $table->string('flight_number', 10);
            $table->string('flight_icao', 10);
            $table->unsignedBigInteger('airport_dep_id')->nullable();
            $table->string('dep_icao', 4);
            $table->unsignedBigInteger('airport_arr_id')->nullable();
            $table->string('arr_icao', 4);
            $table->string('aircraft_icao', 4);
            $table->string('reg_number')->nullable();
            $table->unsignedInteger('counter')->default(0);
            $table->timestamps();

            $table->unique(['dep_icao', 'arr_icao', 'flight_icao']);
            $table->foreign('airport_dep_id')->references('id')->on('airports');
            $table->foreign('airport_arr_id')->references('id')->on('airports');

            // Also create indexes for faster queries
            $table->index('dep_icao');
            $table->index('arr_icao');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('flights');
    }
};
