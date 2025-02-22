<?php

use App\Models\Airport;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MatanYadaev\EloquentSpatial\Enums\Srid;
use MatanYadaev\EloquentSpatial\Objects\Point;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add geometry column
        Schema::table('airports', function (Blueprint $table) {
            $table->geometry('coordinates', subtype: 'point')->after('name')->nullable();
        });

        // Convert double latitude_deg and longitude_deg to geometry
        $airports = Airport::select('id', 'latitude_deg', 'longitude_deg')->get();
        foreach ($airports as $airport) {

            // We do this without upsert and optimization as this column type didn't support upsert and caused point errors.
            $airport->coordinates = new Point($airport->latitude_deg, $airport->longitude_deg, Srid::WGS84->value);
            $airport->save();
        }

        // Add spatial index which also requires not null
        Schema::table('airports', function (Blueprint $table) {
            $table->geometry('coordinates', subtype: 'point')->nullable(false)->change();
            $table->spatialIndex('coordinates');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('airports', function (Blueprint $table) {
            $table->dropColumn('coordinates');
        });
    }
};
