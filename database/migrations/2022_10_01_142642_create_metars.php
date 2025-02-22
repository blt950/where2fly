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
        Schema::create('metars', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('airport_id')->unique();
            $table->timestamp('last_update');
            $table->string('metar');
            $table->integer('wind_direction')->nullable();
            $table->unsignedInteger('wind_speed')->nullable();
            $table->unsignedInteger('wind_gusts')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('metars');
    }
};
