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
        Schema::table('scenery_simulators', function (Blueprint $table) {
            // Add a plain index so the foreign key on scenery_id still has
            // index support after the composite unique index is removed.
            // MySQL refuses to drop an index that is the sole index backing a FK.
            $table->index('scenery_id');
            $table->dropUnique(['scenery_id', 'simulator_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scenery_simulators', function (Blueprint $table) {
            $table->unique(['scenery_id', 'simulator_id']);
            $table->dropIndex(['scenery_id']);
        });
    }
};
