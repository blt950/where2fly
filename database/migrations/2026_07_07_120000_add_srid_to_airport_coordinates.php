<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * MySQL's optimizer ignores SPATIAL indexes on columns without an SRID
     * restriction, so the airports_coordinates_spatialindex has never been
     * usable. All stored points already carry SRID 4326 — restrict the column
     * and rebuild the index on top of it so the bounding-box pre-filter in
     * Airport::withinDistance (and withinBearing's polygon) can use it.
     */
    public function up(): void
    {
        Schema::table('airports', function (Blueprint $table) {
            $table->dropSpatialIndex(['coordinates']);
        });

        Schema::table('airports', function (Blueprint $table) {
            $table->geometry('coordinates', subtype: 'point', srid: 4326)->nullable(false)->change();
        });

        Schema::table('airports', function (Blueprint $table) {
            $table->spatialIndex('coordinates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('airports', function (Blueprint $table) {
            $table->dropSpatialIndex(['coordinates']);
        });

        Schema::table('airports', function (Blueprint $table) {
            $table->geometry('coordinates', subtype: 'point')->nullable(false)->change();
        });

        Schema::table('airports', function (Blueprint $table) {
            $table->spatialIndex('coordinates');
        });
    }
};
