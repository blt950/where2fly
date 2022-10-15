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
        Schema::create('airports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('icao', 7);
            $table->enum('type', ['balloonport', 'closed', 'heliport', 'large_airport', 'medium_airport', 'seaplane_base', 'small_airport']);
            $table->string('name');
            $table->float('latitude_deg');
            $table->float('longitude_deg');
            $table->integer('elevation_ft')->nullable();
            $table->string('continent', 2);
            $table->string('iso_country', 2);
            $table->string('iso_region');
            $table->string('municipality');
            $table->string('gps_code')->nullable();
            $table->string('iata_code')->nullable();
            $table->string('local_code')->nullable();
            $table->unsignedInteger('total_core')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('airports');
    }
};
