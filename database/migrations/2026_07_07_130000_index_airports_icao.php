<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Anchor lookups, the AirportExists rule, notIcao and the icao whitelists
     * all filter on this column. Plain index, not unique — the ourairports
     * data contains duplicate icao idents (e.g. SA20, SNHA).
     */
    public function up(): void
    {
        Schema::table('airports', function (Blueprint $table) {
            $table->index('icao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('airports', function (Blueprint $table) {
            $table->dropIndex(['icao']);
        });
    }
};
