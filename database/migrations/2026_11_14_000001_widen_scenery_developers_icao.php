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
        // Match airports.icao, so sceneries can attach to airports with local idents like 09J or RO-0017
        Schema::table('scenery_developers', function (Blueprint $table) {
            $table->string('icao', 10)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scenery_developers', function (Blueprint $table) {
            $table->string('icao', 4)->change();
        });
    }
};
