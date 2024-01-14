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
        Schema::create('flight_aircraft', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flight_id');
            $table->string('aircraft_icao', 4);

            $table->timestamp('last_seen_at')->default(DB::raw('(UTC_TIMESTAMP)'));
            $table->timestamp('first_seen_at')->default(DB::raw('(UTC_TIMESTAMP)'));

            $table->foreign('flight_id')->references('id')->on('flights')->onDelete('CASCADE');
            $table->unique(['flight_id', 'aircraft_icao']);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('flight_aircraft');
    }
};
