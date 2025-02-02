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
        Schema::rename('sceneries', 'scenery_developers');
        Schema::table('scenery_developers', function (Blueprint $table) {
            $table->unique(['icao', 'developer']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scenery_developers', function (Blueprint $table) {
            $table->dropUnique(['icao', 'developer']);
        });
        Schema::rename('scenery_developers', 'sceneries');
    }
};
