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
        Schema::rename('scenery_simulators', 'sceneries');
        Schema::table('sceneries', function (Blueprint $table) {
            $table->id()->first();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('sceneries', 'scenery_simulators');
        Schema::table('scenery_simulators', function (Blueprint $table) {
            $table->dropColumn('id');
        });
    }
};
