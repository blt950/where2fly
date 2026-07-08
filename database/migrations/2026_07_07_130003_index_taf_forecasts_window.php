<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The METAR-fallback whereNotExists in applyCoversEta ranges on
     * valid_from/valid_to per score row per candidate airport.
     */
    public function up(): void
    {
        Schema::table('taf_forecasts', function (Blueprint $table) {
            $table->index(['taf_id', 'valid_from', 'valid_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taf_forecasts', function (Blueprint $table) {
            $table->dropIndex(['taf_id', 'valid_from', 'valid_to']);
        });
    }
};
