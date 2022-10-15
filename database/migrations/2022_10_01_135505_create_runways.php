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
        Schema::create('runways', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('airport_id', false, true);
            $table->string('airport_ident', 7);
            $table->integer('length_ft')->nullable();
            $table->integer('width_ft')->nullable();
            $table->string('surface')->nullable();
            $table->boolean('lighted');
            $table->boolean('closed');
            $table->string('le_ident', 10)->nullable();
            $table->float('le_heading', 4, 1, true)->nullable();
            $table->string('he_ident', 10)->nullable();
            $table->float('he_heading', 4, 1, true)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('runways');
    }
};
